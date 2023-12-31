<?php
    
namespace Hypertext\Bundle\Traits;

trait HypertextFactoryTrait
{
    protected function random_string($lenght = 8, $alphabet = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz')
    {
        return substr(str_shuffle((string)$alphabet), 0, (integer)$lenght);
    }

    /**
     * Encrypt password with APR1-MD5 Apache method
     *
     * @param string $password
     * @param string|null $salt
     * @return string Encrypted string
     * @ref https://stackoverflow.com/a/8786956
     */ 
    protected function crypt_apr1_md5($password, $salt = NULL)
    {
        if (!$salt) $salt = str_shuffle('./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz');
        $salt = substr($salt, 0, 8);
        
        $len = strlen($password);
        $text = $password.'$apr1$'.$salt;
        $bin = pack("H32", md5($password.$salt.$password));

        for ($i = $len; $i > 0; $i -= 16) $text .= substr($bin, 0, min(16, $i));
        for ($i = $len; $i > 0; $i >>= 1) $text .= ($i & 1) ? chr(0) : $password[0];

        $bin = pack("H32", md5($text));

        for ($i = 0; $i < 1000; $i++)
        {
            $new = ($i & 1) ? $password : $bin;
            if ($i % 3) $new .= $salt;
            if ($i % 7) $new .= $password;
            $new .= ($i & 1) ? $bin : $password;
            $bin = pack("H32", md5($new));
        }

        $tmp = '';
        for ($i = 0; $i < 5; $i++)
        {
            $k = $i + 6;
            $j = $i + 12;
            if ($j == 16) $j = 5;
            $tmp = $bin[$i].$bin[$k].$bin[$j].$tmp;
        }

        $tmp = chr(0).chr(0).$bin[11].$tmp;
        $tmp = strtr(strrev(substr(base64_encode($tmp), 2)),
        "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/",
        "./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz");
        
        return '$apr1$'.$salt.'$'.$tmp;
    }

    /**
     * Encrypt password
     *
     * @param string $password
     * @return string Encrypted password
     *
     * @see http://httpd.apache.org/docs/2.2/misc/password_encryptions.html
     */ 
    protected function encrypt_password($password, $encryption = 'md5')
    {
        $result = FALSE;
        $password = (string)$password;
        
        switch ($encryption)
        {
            case 'crypt':
                // Unix only. Uses the traditional Unix crypt(3) function with a randomly-generated 32-bit salt (only 12 bits used) and the first 8 characters of the password.
                $salt = $this->random_string(2);  // Salt must be 2 char range ./0-9A-Za-z
                $result = crypt($password, $salt);
                break;

            case 'sha':
                // "{SHA}" + Base64-encoded SHA-1 digest of the password.
                $result = '{SHA}'.base64_encode(sha1($password, true));
                break;

            case 'md5':
            // "$apr1$" + the result of an Apache-specific algorithm using an iterated (1,000 times) MD5 digest of various combinations of a random 32-bit salt and the password.
            // See the APR source file apr_md5.c for the details of the algorithm.
            default:
                $salt = $this->random_string(8); // Salt must be 8 char range ./0-9A-Za-z
                $result = $this->crypt_apr1_md5($password, $salt);
                break;
        }
        
        return $result;
    }

    /**
     * Get .htpasswd file contents
     * Looks for .htpasswd file path in .htaccess if provided
     *
     * @param string $path Full path to file: '/path/to/.htpasswd' or '/path/to/.htaccess'
     * @return array Array with keys 'path' and 'contents', where values can be:
     *               string with .htpasswd contents (empty string means empty file)
     *               NULL when file does not exists
     *               FALSE on failure
     */ 
    protected function file_get_htpasswd($path = '.htaccess')
    {
        $file = [
            'path' => $path,
            'contents' => NULL,
        ];
            
        // If .htpasswd path file provided
        if (strpos($file['path'], '.htpasswd') !== FALSE)
        {
            if (file_exists($file['path'])) $file['contents'] = file_get_contents($path);
            return $file;
        }

        if (strpos($file['path'], '.htaccess') !== FALSE)
        {
            if (file_exists($file['path'])) $file['contents'] = file_get_contents($path);
            if ($file['contents'] === FALSE) return FALSE;
        }
        else return FALSE;
        
        // Then .htaccess path provided and we're looking for .htpasswd path
        $file['path'] = NULL;
        $htaccess_lines = explode("\n", $file['contents']);

        foreach ($htaccess_lines as $line)
        {
            if ($line)
            {
                $line_parts = preg_split('/\s+/', $line); // Split spaces and tabs
                if (strcmp($line_parts[0], 'AuthUserFile') == 0) $file['path'] = preg_replace('/"/', '', $line_parts[1]); // Clean quotes and double quotes
            }
            if ($file['path'] !== NULL) break;
        }
        
        $file['contents'] = NULL;
        if ($file['path'] && file_exists($file['path'])) $file['contents'] = file_get_contents($file['path']);
        
        return $file;
    }

    /**
     * Save .htpasswd file contents
     *
     * @param array $passords An array of ('login' => 'password') pairs.
     * @param string $path Optional. Full path to file: '/path/to/.htpasswd'. Default is current directory.
     * @param string $encription Optional. Encryption method: crypt, sha, md5. Default is md5.
     * @return integer|bool Number of bytes that were written to the file, or FALSE on failure.
     */ 
    protected function file_put_htpasswd($passwords = array(), $path = '.htpasswd', $encryption = 'md5')
    {
        $contents = '';
        
        $passwords = $this->array_flatten($passwords);
        foreach ($passwords as $login => $password)
        {
            $password = $this->encrypt_password($password, $encryption);
            $contents .= "{$login}:{$password}\n";        
        }
        
        return file_put_contents($path, $contents);
    }

    protected function array_flatten($array, $prefix = '') {
        $result = array();
        foreach($array as $key=>$value) {
            if(is_array($value)) {
                $result = $result + $this->array_flatten($value, $prefix . $key . '.');
            }
            else {
                $result[$prefix . $key] = $value;
            }
        }
        return $result;
    }
}