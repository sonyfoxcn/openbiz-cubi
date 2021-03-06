<?php 
/**
 * WINNT System Class
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PSI_OS
 * @author    Michael Cramer <BigMichi1@users.sourceforge.net>
 * @copyright 2009 phpSysInfo
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @version   SVN: $Id: class.WINNT.inc.php 522 2011-11-08 18:21:09Z jacky672 $
 * @link      http://phpsysinfo.sourceforge.net
 */
 /**
 * WINNT sysinfo class
 * get all the required information from WINNT systems
 * information are retrieved through the WMI interface
 *
 * @category  PHP
 * @package   PSI_OS
 * @author    Michael Cramer <BigMichi1@users.sourceforge.net>
 * @copyright 2009 phpSysInfo
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @version   Release: 3.0
 * @link      http://phpsysinfo.sourceforge.net
 */
class WINNT extends OS
{
    /**
     * holds the COM object that we pull all the WMI data from
     *
     * @var Object
     */
    private $_wmi;
    
    /**
     * holds all devices, which are in the system
     *
     * @var array
     */
    private $_wmidevices;
    
    /**
     * store language encoding of the system to convert some output to utf-8
     *
     * @var string
     */
    private $_charset = "";
    
    /**
     * build the global Error object and create the WMI connection
     */
    public function __construct()
    {
        parent::__construct();
        // don't set this params for local connection, it will not work
        $strHostname = '';
        $strUser = '';
        $strPassword = '';
        
        // initialize the wmi object
        $objLocator = new COM('WbemScripting.SWbemLocator');
        if ($strHostname == "") {
            $this->_wmi = $objLocator->ConnectServer();
        } else {
            $this->_wmi = $objLocator->ConnectServer($strHostname, 'rootcimv2', $strHostname.'\\'.$strUser, $strPassword);
        }
        $this->_getCodeSet();
    }
    
    /**
     * store the codepage of the os for converting some strings to utf-8
     *
     * @return void
     */
    private function _getCodeSet()
    {
        $buffer = $this->_getWMI('Win32_OperatingSystem', array('CodeSet'));
        $this->_charset = 'windows-'.$buffer[0]['CodeSet'];
    }
    
    /**
     * function for getting a list of values in the specified context
     * optionally filter this list, based on the list from second parameter
     *
     * @param string $strClass name of the class where the values are stored
     * @param array  $strValue filter out only needed values, if not set all values of the class are returned
     *
     * @return array content of the class stored in an array
     */
    private function _getWMI($strClass, $strValue = array())
    {
        $arrData = array();
        $value = "";
        try {
            $objWEBM = $this->_wmi->Get($strClass);
            $arrProp = $objWEBM->Properties_;
            $arrWEBMCol = $objWEBM->Instances_();
            foreach ($arrWEBMCol as $objItem) {
                if (is_array($arrProp)) {
                    reset($arrProp);
                }
                $arrInstance = array();
                foreach ($arrProp as $propItem) {
                    eval("\$value = \$objItem->".$propItem->Name.";");
                    if ( empty($strValue)) {
                        $arrInstance[$propItem->Name] = trim($value);
                    } else {
                        if (in_array($propItem->Name, $strValue)) {
                            $arrInstance[$propItem->Name] = trim($value);
                        }
                    }
                }
                $arrData[] = $arrInstance;
            }
        }
        catch(Exception $e) {
            if (PSI_DEBUG) {
                $this->error->addError($e->getCode(), $e->getMessage());
            }
        }
        return $arrData;
    }
    
    /**
     * retrieve different device types from the system based on selector
     *
     * @param string $strType type of the devices that should be returned
     *
     * @return array list of devices of the specified type
     */
    private function _devicelist($strType)
    {
        if ( empty($this->_wmidevices)) {
            $this->_wmidevices = $this->_getWMI('Win32_PnPEntity', array('Name', 'PNPDeviceID'));
        }
        $list = array();
        foreach ($this->_wmidevices as $device) {
            if (substr($device['PNPDeviceID'], 0, strpos($device['PNPDeviceID'], "\\") + 1) == ($strType."\\")) {
                $list[] = $device['Name'];
            }
        }
        return $list;
    }
    
    /**
     * Host Name
     *
     * @return void
     */
    private function _hostname()
    {
        if (PSI_USE_VHOST === true) {
            $this->sys->setHostname(getenv('SERVER_NAME'));
        } else {
            $buffer = $this->_getWMI('Win32_ComputerSystem', array('Name'));
            $result = $buffer[0]['Name'];
            $ip = gethostbyname($result);
            if ($ip != $result) {
                $this->sys->setHostname(gethostbyaddr($ip));
            }
        }
    }
    
    /**
     * IP of the Canonical Host Name
     *
     * @return void
     */
    private function _ip()
    {
        if (PSI_USE_VHOST === true) {
            $this->sys->setIp(gethostbyname($this->_hostname()));
        } else {
            $buffer = $this->_getWMI('Win32_ComputerSystem', array('Name'));
            $result = $buffer[0]['Name'];
            $this->sys->setIp(gethostbyname($result));
        }
    }
    
    /**
     * UpTime
     * time the system is running
     *
     * @return void
     */
    private function _uptime()
    {
        $result = 0;
        date_default_timezone_set('UTC');
        $buffer = $this->_getWMI('Win32_OperatingSystem', array('LastBootUpTime', 'LocalDateTime'));
        $byear = intval(substr($buffer[0]['LastBootUpTime'], 0, 4));
        $bmonth = intval(substr($buffer[0]['LastBootUpTime'], 4, 2));
        $bday = intval(substr($buffer[0]['LastBootUpTime'], 6, 2));
        $bhour = intval(substr($buffer[0]['LastBootUpTime'], 8, 2));
        $bminute = intval(substr($buffer[0]['LastBootUpTime'], 10, 2));
        $bseconds = intval(substr($buffer[0]['LastBootUpTime'], 12, 2));
        $lyear = intval(substr($buffer[0]['LocalDateTime'], 0, 4));
        $lmonth = intval(substr($buffer[0]['LocalDateTime'], 4, 2));
        $lday = intval(substr($buffer[0]['LocalDateTime'], 6, 2));
        $lhour = intval(substr($buffer[0]['LocalDateTime'], 8, 2));
        $lminute = intval(substr($buffer[0]['LocalDateTime'], 10, 2));
        $lseconds = intval(substr($buffer[0]['LocalDateTime'], 12, 2));
        $boottime = mktime($bhour, $bminute, $bseconds, $bmonth, $bday, $byear);
        $localtime = mktime($lhour, $lminute, $lseconds, $lmonth, $lday, $lyear);
        $result = $localtime - $boottime;
        $this->sys->setUptime($result);
    }
    
    /**
     * Number of Users
     *
     * @return void
     */
    private function _users()
    {
        $users = 0;
        $buffer = $this->_getWMI('Win32_Process', array('Caption'));
        foreach ($buffer as $process) {
            if (strtoupper($process['Caption']) == strtoupper('explorer.exe')) {
                $users++;
            }
        }
        $this->sys->setUsers($users);
    }
    
    /**
     * Distribution
     *
     * @return void
     */
    private function _distro()
    {
        $buffer = $this->_getWMI('Win32_OperatingSystem', array('Version', 'ServicePackMajorVersion'));
        $kernel = $buffer[0]['Version'];
        if ($buffer[0]['ServicePackMajorVersion'] > 0) {
            $kernel .= ' SP'.$buffer[0]['ServicePackMajorVersion'];
        }
        $this->sys->setKernel($kernel);
        
        $buffer = $this->_getWMI('Win32_OperatingSystem', array('Caption'));
        $this->sys->setDistribution($buffer[0]['Caption']);
        
        if ($kernel[0] == 6) {
            $icon = 'vista.png';
        } else {
            $icon = 'xp.png';
        }
        $this->sys->setDistributionIcon($icon);
    }
    
    /**
     * Processor Load
     * optionally create a loadbar
     *
     * @return void
     */
    private function _loadavg()
    {
        $loadavg = "";
        $sum = 0;
        $buffer = $this->_getWMI('Win32_Processor', array('LoadPercentage'));
        foreach ($buffer as $load) {
            $value = $load['LoadPercentage'];
            $loadavg .= $value.' ';
            $sum += $value;
        }
        $this->sys->setLoad(trim($loadavg));
        if (PSI_LOAD_BAR) {
            $this->sys->setLoadPercent($sum / count($buffer));
        }
    }
    
    /**
     * CPU information
     *
     * @return void
     */
    private function _cpuinfo()
    {
        $allCpus = $this->_getWMI('Win32_Processor', array('Name', 'L2CacheSize', 'CurrentClockSpeed', 'ExtClock', 'NumberOfCores'));
        foreach ($allCpus as $oneCpu) {
            $coreCount = 1;
            if (isset($oneCpu['NumberOfCores'])) {
                $coreCount = $oneCpu['NumberOfCores'];
            }
            for ($i = 0; $i < $coreCount; $i++) {
                $cpu = new CpuDevice();
                $cpu->setModel($oneCpu['Name']);
                $cpu->setCache($oneCpu['L2CacheSize'] * 1024);
                $cpu->setCpuSpeed($oneCpu['CurrentClockSpeed']);
                $cpu->setBusSpeed($oneCpu['ExtClock']);
                $this->sys->setCpus($cpu);
            }
        }
    }
    
    /**
     * Hardwaredevices
     *
     * @return void
     */
    private function _hardware()
    {
        foreach ($this->_devicelist('PCI') as $pciDev) {
            $dev = new HWDevice();
            $dev->setName($pciDev);
            $this->sys->setPciDevices($dev);
        }
        
        foreach ($this->_devicelist('IDE') as $ideDev) {
            $dev = new HWDevice();
            $dev->setName($ideDev);
            $this->sys->setIdeDevices($dev);
        }
        
        foreach ($this->_devicelist('SCSI') as $scsiDev) {
            $dev = new HWDevice();
            $dev->setName($scsiDev);
            $this->sys->setScsiDevices($dev);
        }
        
        foreach ($this->_devicelist('USB') as $usbDev) {
            $dev = new HWDevice();
            $dev->setName($usbDev);
            $this->sys->setUsbDevices($dev);
        }
    }
    
    /**
     * Network devices
     *
     * @return void
     */
    private function _network()
    {
        foreach ($this->_getWMI('Win32_PerfRawData_Tcpip_NetworkInterface') as $device) {
            $dev = new NetDevice();
            $dev->setName($device['Name']);
            // http://msdn.microsoft.com/library/default.asp?url=/library/en-us/wmisdk/wmi/win32_perfrawdata_tcpip_networkinterface.asp
            // there is a possible bug in the wmi interfaceabout uint32 and uint64: http://www.ureader.com/message/1244948.aspx, so that
            // magative numbers would occour, try to calculate the nagative value from total - positive number
            $txbytes = $device['BytesSentPersec'];
            if ($txbytes < 0) {
                $txbytes = $device['BytesTotalPersec'] - $device['BytesReceivedPersec'];
                
            }
            $dev->setTxBytes($txbytes);
            $rxbytes = $device['BytesReceivedPersec'];
            if ($rxbytes < 0) {
                $rxbytes = $device['BytesTotalPersec'] - $device['BytesSentPersec'];
            }
            $dev->setRxBytes($rxbytes);
            $dev->setErrors($device['PacketsReceivedErrors']);
            $dev->setDrops($device['PacketsReceivedDiscarded']);
            $this->sys->setNetDevices($dev);
        }
    }
    
    /**
     * Physical memory information and Swap Space information
     *
     * @link http://msdn2.microsoft.com/En-US/library/aa394239.aspx
     * @link http://msdn2.microsoft.com/en-us/library/aa394246.aspx
     * @return void
     */
    private function _memory()
    {
        $buffer = $this->_getWMI("Win32_OperatingSystem", array('TotalVisibleMemorySize', 'FreePhysicalMemory'));
        $this->sys->setMemTotal($buffer[0]['TotalVisibleMemorySize'] * 1024);
        $this->sys->setMemFree($buffer[0]['FreePhysicalMemory'] * 1024);
        $this->sys->setMemUsed($this->sys->getMemTotal() - $this->sys->getMemFree());
        
        $buffer = $this->_getWMI('Win32_PageFileUsage');
        foreach ($buffer as $swapdevice) {
            $dev = new DiskDevice();
            $dev->setName("SWAP");
            $dev->setMountPoint($swapdevice['Name']);
            $dev->setTotal($swapdevice['AllocatedBaseSize'] * 1024 * 1024);
            $dev->setUsed($swapdevice['CurrentUsage'] * 1024 * 1024);
            $dev->setFree($dev->getTotal() - $dev->getUsed());
            $dev->setFsType('swap');
            $this->sys->setSwapDevices($dev);
        }
    }
    
    /**
     * filesystem information
     *
     * @return void
     */
    private function _filesystems()
    {
        $typearray = array('Unknown', 'No Root Directory', 'Removable Disk', 'Local Disk', 'Network Drive', 'Compact Disc', 'RAM Disk');
        $floppyarray = array('Unknown', '5 1/4 in.', '3 1/2 in.', '3 1/2 in.', '3 1/2 in.', '3 1/2 in.', '5 1/4 in.', '5 1/4 in.', '5 1/4 in.', '5 1/4 in.', '5 1/4 in.', 'Other', 'HD', '3 1/2 in.', '3 1/2 in.', '5 1/4 in.', '5 1/4 in.', '3 1/2 in.', '3 1/2 in.', '5 1/4 in.', '3 1/2 in.', '3 1/2 in.', '8 in.');
        $buffer = $this->_getWMI('Win32_LogicalDisk', array('Name', 'Size', 'FreeSpace', 'FileSystem', 'DriveType', 'MediaType'));
        foreach ($buffer as $filesystem) {
            $dev = new DiskDevice();
            $dev->setMountPoint($filesystem['Name']);
            $dev->setFsType($filesystem['FileSystem']);
            if ($filesystem['Size'] > 0) {
                $dev->setTotal($filesystem['Size']);
                $dev->setFree($filesystem['FreeSpace']);
                $dev->setUsed($filesystem['Size'] - $filesystem['FreeSpace']);
            }
            if ($filesystem['MediaType'] != "" && $filesystem['DriveType'] == 2) {
                $dev->setName($typearray[$filesystem['DriveType']]." (".$floppyarray[$filesystem['MediaType']].")");
            } else {
                $dev->setName($typearray[$filesystem['DriveType']]);
            }
            $this->sys->setDiskDevices($dev);
        }
    }
    
    /**
     * get os specific encoding
     *
     * @see OS::getEncoding()
     *
     * @return string
     */
    function getEncoding()
    {
        return $this->_charset;
    }
    
    /**
     * get the information
     *
     * @see PSI_Interface_OS::build()
     *
     * @return Void
     */
    function build()
    {
        $this->_ip();
        $this->_hostname();
        $this->_distro();
        $this->_users();
        $this->_uptime();
        $this->_cpuinfo();
        $this->_network();
        $this->_hardware();
        $this->_filesystems();
        $this->_memory();
        $this->_loadavg();
    }
}
?>
