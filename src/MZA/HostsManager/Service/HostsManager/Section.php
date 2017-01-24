<?php

namespace MZA\HostsManager\Service\HostsManager;

class Section
{
    protected $key;
    protected $name;
    protected $hosts;

    /**
     * Section constructor.
     * @param $name
     * @param array $hosts
     */
    public function __construct($name, $hosts = [])
    {
        $this->name = trim($name);
        $this->hosts = $hosts;

        // Generate hash key from name
        $this->key = self::generateKey($this->name);
    }

    public static function generateKey($name)
    {
        return sha1(trim($name));
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     * @return Host
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return array
     */
    public function getHosts()
    {
        return $this->hosts;
    }

    /**
     * @param array $hosts
     * @return Host
     */
    public function setHosts($hosts)
    {
        $this->hosts = $hosts;
        return $this;
    }

    /**
     * @param $name
     * @param $ip
     * @return $this
     */
    public function addHost($name, $ip)
    {
        if (!array_key_exists($name, $this->hosts)) {
            $this->hosts[$name] = new Host($name, $ip, $this);
        }
        $this->hosts[$name]->setName($name)->setIp($ip);

        return $this;
    }

    /**
     * @param Host $host
     */
    public function registerHost($host)
    {
        $this->hosts[$host->getName()] = $host->setSection($this);

        return $this;
    }

    /**
     * @param Host $host
     */
    public function unRegisterHost($host)
    {
        /** @var Host $tmpHost */
        $tmpHost = $this->hosts[$host->getName()];
        unset($this->hosts[$host->getName()]);
        $tmpHost->setSection(null);

        return $this;
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
}