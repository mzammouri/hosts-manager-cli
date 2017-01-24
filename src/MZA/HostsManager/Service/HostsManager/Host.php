<?php

namespace MZA\HostsManager\Service\HostsManager;

class Host
{
    protected $name;
    protected $ip;
    protected $section;

    /**
     * Host constructor.
     * @param $name
     * @param $ip
     * @param null|Section $section
     */
    public function __construct($name, $ip, $section)
    {
        $this->name = $name;
        $this->ip = $ip;
        $this->section = $section;

        // Register host in section if not yet registered
        $this->section->registerHost($this);

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
     * @return mixed
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param mixed $ip
     * @return Host
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
        return $this;
    }

    /**
     * @return Section
     */
    public function getSection()
    {
        return $this->section;
    }

    /**
     * @param Section $section
     * @return Host
     */
    public function setSection($section)
    {
        // Remove host form old section hosts array
        if (is_null($section) && $this->section) {
            $tmpSection = $this->section;
            $this->section = null;
            $tmpSection->unRegisterHost($this);
            return $this;
        }

        // Register hosts in section hosts array
        if (!is_null($section) && $this->section->getKey() !== $section->getKey()) {
            $this->section = $section;
            $this->section->registerHost($this);
        }

        return $this;
    }
}