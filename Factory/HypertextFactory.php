<?php

namespace Hypertext\Bundle\Factory;

use Hypertext\Bundle\HypertextBundle;
use Hypertext\Bundle\Traits\HypertextFactoryTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

class HypertextFactory
{
    use HypertextFactoryTrait;

    /**
     * @var ParameterBagInterface
     */
    protected $parameterBag;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var bool
     */
    protected bool $enable;

    /**
     * @var string
     */
    protected string $publicDir;

    public const GENERATION_PREFIX = "# These lines below were automatically generated by";

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;

        $this->filesystem            = new Filesystem();
        $this->enable                = $this->parameterBag->get("hypertext.enable");

        $this->publicDir = $this->parameterBag->get('kernel.project_dir')."/public";
    }

    public function format(string $path, ?string $stripPrefix = "")
    {
        if (str_contains($path, "@")) {
            return "mailto: ".str_lstrip(trim($path), "mailto:");
        }
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        if (str_starts_with($path, "/")) {
            return str_lstrip($this->getPublicDir().$path, $stripPrefix);
        }

        $dir = $this->getPublicDir();
        $this->filesystem->mkdir($dir, 0777, true);

        return str_lstrip($dir."/".$path, $stripPrefix);
    }

    public function getPublicDir(): string
    {
        return $this->publicDir;
    }

    public function isSafePlace($fname)
    {
        if ($fname === null) {
            return false;
        }
        if (filter_var($fname, FILTER_VALIDATE_URL)) {
            return true;
        }
        if (str_starts_with($fname, $this->getPublicDir())) {
            $base = explode("/", str_lstrip($fname, $this->getPublicDir()), 1)[0];
            if (!in_array($base, ["bundles", "assets", "storage"])) {
                return true;
            }
        }

        return false;
    }

    public function htpasswd()
    {
        $htpasswd     = $this->parameterBag->get("hypertext.access.auth_user_file") ?? null;
        $htpasswdList = $this->parameterBag->get("hypertext.password") ?? [];
        $encrypt = $this->parameterBag->get("hypertext.encrypt");

        foreach(glob($this->publicDir."/.htpasswd*") as $f) {
            unlink($f);
        }

        if($this->enable) {

            foreach($htpasswdList as $id => $entry)
            {
                $fname = $this->format(".htpasswd.".$id);
                if (!$this->isSafePlace($fname)) {
                    return null;
                }

                $suffix = $htpasswd == $id ? "" : ".".$id;
                $this->file_put_htpasswd($entry, $this->publicDir."/.htpasswd".$suffix, $encrypt);
            }
        }
    }

    public function htaccess(): ?string
    {
        $fname = $this->format(".htaccess");
        if (!$this->isSafePlace($fname)) {
            return null;
        }

        $htaccess = $this->filesystem->exists($fname) ? file_get_contents($fname) : "";
        $htaccess = explode("\n", $htaccess);
        $eof = array_keys(array_filter($htaccess, fn($line) => str_contains($line, HypertextBundle::BUNDLE_NAME)))[0] ?? null;
        $htaccess = array_slice($htaccess, 0, $eof);

        if($this->enable) {

            $htaccess[] = self::GENERATION_PREFIX." ".HypertextBundle::BUNDLE_NAME." on ".date("Y-m-d") ." at ". date("H:i:s");
            $htaccess[] = "";

            $errorDocuments = $this->parameterBag->get("hypertext.access.error_document") ?? [];
            foreach($errorDocuments as $code => $document) {
                $htaccess[] = "ErrorDocument " . $code. " " . $document;
            }
            if($errorDocuments) $htaccess[] = "";
            
            // Default .htaccess auth
            $defaultAuthName = $this->parameterBag->get("hypertext.access.auth_name") ?? "Dialog prompt";
            $defaultAuthType = $this->parameterBag->get("hypertext.access.auth_type") ?? "Basic";
            $defaultAuthUserFile = $this->parameterBag->get("hypertext.access.auth_user_file") ?? null;
            if($defaultAuthUserFile) { 
                
                $htaccess[] = "AuthName '" . $defaultAuthName . "'";
                $htaccess[] = "AuthType " . $defaultAuthType;
                $htaccess[] = "AuthUserFile ". $this->publicDir."/.htpasswd";
                $htaccess[] = "Require valid-user";
                $htaccess[] = "";
            }

            // File by file .htaccess auth
            $filesEntries = $this->parameterBag->get("hypertext.access.files") ?? [];
            foreach($filesEntries as $filesEntry) {

                $files = $filesEntry["name"] ?? ".*";
                $htaccess[] = "<Files '".$files."'>";

                $authName = $filesEntry["auth_name"] ?? "Dialog prompt";
                $authType = $filesEntry["auth_type"] ?? "Basic";
                $authUserFile = $filesEntry["auth_user_file"] ?? null;
                $suffix = $authUserFile == $defaultAuthUserFile ? "" : ".".$authUserFile;

                if($authUserFile) {

                    $htaccess[] = "\tAuthName '" . $authName . "'";
                    $htaccess[] = "\tAuthType " . $authType;
                    $htaccess[] = "\tAuthUserFile ". $this->publicDir."/.htpasswd".$suffix;
                    $htaccess[] = "\tRequire valid-user";
                }

                $htaccess[] = "</Files>";
                $htaccess[] = "";
            }

            // File match .htaccess auth
            $filesMatchEntries = $this->parameterBag->get("hypertext.access.files_match") ?? [];
            foreach($filesMatchEntries as $filesMatchEntry) {

                $filesMatch = $filesMatchEntry["pattern"] ?? ".*";
                $htaccess[] = "<FilesMatch '".$filesMatch."'>";

                $authName = $filesMatchEntry["auth_name"] ?? "Dialog prompt";
                $authType = $filesMatchEntry["auth_type"] ?? "Basic";
                $authUserFile = $filesMatchEntry["auth_user_file"] ?? null;
                $suffix = $authUserFile == $defaultAuthUserFile ? "" : ".".$authUserFile;

                if($authUserFile) {

                    $htaccess[] = "\tAuthName '" . $authName . "'";
                    $htaccess[] = "\tAuthType " . $authType;
                    $htaccess[] = "\tAuthUserFile ". $this->publicDir."/.htpasswd".$suffix;
                    $htaccess[] = "\tRequire valid-user";
                }

                $htaccess[] = "</FilesMatch>";
                $htaccess[] = "";
            }
        }

        while( \str_starts_with(end($htaccess), self::GENERATION_PREFIX) || empty(trim(end($htaccess))) )
            array_pop($htaccess);

        $htaccess[] = ""; // EOF

        $htaccess = implode(PHP_EOL, $htaccess);
        $this->filesystem->dumpFile($fname, $htaccess);

        return $htaccess ? $fname : null;
    }
}
