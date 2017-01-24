<?php

namespace MZA\HostsManager\Service;

use MZA\HostsManager\Service\HostsManager\Host;
use MZA\HostsManager\Service\HostsManager\Section;

class HostsManager implements \Iterator
{
    const HOSTS_FILE_WIN = 'C:\Windows\System32\drivers\etc\hosts';
    const HOSTS_FILE_UNX = '/etc/hosts';

    //
    const PREFIX_SECTION = '#:';

    /**
     * @var \SplFileObject
     */
    protected $file;

    /**
     * @var array
     */
    protected $hosts = [];

    /**
     * @var array
     */
    protected $sections = [];

    /**
     * HostsManager constructor.
     * @param \SplFileObject $filename
     */
    public function __construct($filename = null)
    {
        if (!is_null($filename)) {
            // Set default hosts file
            $this->setFile($filename);
            $this->buildHosts();
        }
    }

    /**
     * Build hosts array from file
     * @return $this
     */
    public function buildHosts()
    {
        // Reset hosts array
        $this->hosts = [];

        // Retrieve file as SplFileObject
        $hostFile = $this->getFile();

        // Init section by "Others" name section
        $currentSectionN = null;

        foreach ($hostFile as $line) {
            // Ignore line if it does not match pattern for host line OR Section line  (comment line)
            if (!preg_match("/^((" . self::PREFIX_SECTION . ")+|[0-9.]+)/", $line)) {
                continue;
            }

            // Retrieve section line : Section line begin by : #;
            if (preg_match("/^" . self::PREFIX_SECTION . "+/", $line)) {
                $currentSectionN = trim(preg_replace("/^" . self::PREFIX_SECTION . "+/", '', $line));
                continue;
            }
            // Retrieve address-host line
            if (preg_match("/^[0-9.]+/", $line)) {
                $hostNames = trim(preg_replace('/^[0-9.]+/', '', $line));
                $hostIp = trim(str_replace($hostNames, '', $line));
                $hostNames = explode(' ', $hostNames);
                $hostNames = array_map('trim', $hostNames);
                $hostNames = array_unique($hostNames);

                foreach ($hostNames as $hostName) {
                    if (!$hostName) {
                        continue;
                    }

                    $this->addHost($hostName, $hostIp, $currentSectionN);
                }
            }
        }

        return $this;
    }

    /**
     * @return \SplFileObject
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param string $filename
     * @return $this
     */
    public function setFile($filename = null)
    {
        // Set Default if $filename is null (not set)
        if (is_null($filename)) {
            $filename = $this->getDefaultHostsFile();
        }

        $this->file = new \SplFileObject($filename);
        return $this;
    }

    /**
     * @param $hName
     * @param $hIp
     * @param null $sName
     * @return $this
     */
    public function addHost($hName, $hIp, $sName = null)
    {
        if (is_null($sName)) {
            $sName = $this->retrieveSectionName($hName);
        }

        $section = $this->findSection($sName);
        if (!$section) {
            $section = new Section($sName);
        }

        $host = $this->findHost($hName);
        // Host exists but change the section
        if ($host) {
            $host->setIp($hIp);
            $host->setSection(null);
        }

        $section->addHost($hName, $hIp);
        $this->hosts[$hName] = $section->findHost($hName);

        return $this;
    }

    /**
     * Get First Domain name part
     * ex : toto.home.com, return toto
     * @param mixed|Host $host
     * @return mixed
     */
    protected function retrieveSectionName($host)
    {
        $host = is_object($host) ? $host->getName() : $host;
        return preg_replace("/[\-\._][\s\S\w\W]+$/", "", $host);
    }

    /**
     * @param $name
     * @return null|Section
     */
    public function findSection($name)
    {
        $key = Section::generateKey($name);
        /** @var Host $host */
        foreach ($this->hosts as $host) {
            if ($host->getSection()->getKey() === $key) {
                return $host->getSection();
            }
        }

        return null;
    }

    /**
     * @param $name
     * @return null|Host
     */
    public function findHost($name)
    {
        if (array_key_exists($name, $this->hosts)) {
            return $this->hosts[$name];
        }

        return null;
    }

    public function rebuiltSections()
    {
        // Retrieve hosts
        $hosts = $this->getHosts();

        /** @var Host $host */
        foreach ($hosts as $host) {
            $this->addHost($host->getName(), $host->getIp(), $this->retrieveSectionName($host));
        }

        $this->builtSections();

        return $this;
    }

    /**
     * @return array
     */
    public function getHosts($force = false)
    {
        if (!$this->hosts || $force) {
            // Reset hosts array and build from File
            $this->buildHosts();
        }

        return $this->hosts;
    }

    /**
     * Build section array from file
     * @return $this
     */
    public function builtSections()
    {
        $this->sections = [];
        $hosts = $this->getHosts();
        /** @var Host $host */
        foreach ($hosts as $host) {
            $this->sections[$host->getSection()->getKey()] = $host->getSection();
        }

        return $this;
    }

    /**
     * @param string $hostname
     * @return $this
     */
    public function removeHost($hostname)
    {
        if (array_key_exists($hostname, $this->hosts)) {
            $this->hosts[$hostname]->setSection(null);
            unset($this->hosts[$hostname]);
        }

        return $this;
    }

    public function saveFile()
    {
        $this->builtSections();

        $file = $this->getFile();
        $content = $this->asString();

        /** @todo use fwrite */
        //$file->fwrite($content);
        //$file->fflush();

        file_put_contents($file->getRealPath(), $content);

        return $this;
    }

    public function asString()
    {
        $result = '';
        $sections = $this->getSections();
        /** @var Section $section */
        foreach ($sections as $section) {
            $result .= self::PREFIX_SECTION . "\t" . $section->getName() . PHP_EOL;
            /** @var Host $host */
            foreach ($section->getHosts() as $host) {
                $hostLine = str_pad($host->getIp(), 16, ' ') . $host->getName();
                $result .= $hostLine . PHP_EOL;
            }
            $result .= PHP_EOL;
        }

        return $result;
    }

    public function getSections()
    {
        $this->builtSections();

        return $this->sections;
    }

    /**
     * @return string
     */
    public function getDefaultHostsFile()
    {
        if (strtolower(substr(PHP_OS, 0, 3)) === 'win') {
            return self::HOSTS_FILE_WIN;
        }
        return self::HOSTS_FILE_UNX;

    }


    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        // TODO: Implement current() method.
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        // TODO: Implement next() method.
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        // TODO: Implement key() method.
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        // TODO: Implement valid() method.
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        // TODO: Implement rewind() method.
    }
}
