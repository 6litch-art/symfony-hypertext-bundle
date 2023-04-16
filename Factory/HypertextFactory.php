<?php

namespace Hypertext\Bundle\Factory;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

class HypertextFactory
{
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


    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;

        $this->filesystem            = new Filesystem();
        $this->enable                = $this->parameterBag->get("htaccess.enable");

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

    public function htpasswd(array $htpasswdList)
    {
        $fname = $this->format(".htpasswd");
        foreach($htpasswdList as $id => $entry) {

            if (!$this->isSafePlace($fname)) {
                return null;
            }

            $htpasswd = "";
            dump($id, $entry);
            if ($htpasswd)  {

                $this->filesystem->dumpFile($fname, $htpasswd);
            }
        }

        return $htpasswdList;
    }

    public function htaccess(): ?string
    {
        $fname = $this->format(".htaccess");
        if (!$this->isSafePlace($fname)) {
            return null;
        }

        $htaccess = $this->filesystem->exists($fname) ? file_get_contents($fname) : "";
        dump($htaccess);
        exit(1);

        if ($htaccess) {
            $this->filesystem->dumpFile($fname, $htaccess);
        }

        return $htaccess ? $fname : null;
    }
}
