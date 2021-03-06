<?php
/**
 * Openbiz Cubi Application Platform
 *
 * LICENSE http://code.google.com/p/openbiz-cubi/wiki/CubiLicense
 *
 * @package   cubi.system.form
 * @copyright Copyright (c) 2005-2011, Openbiz Technology LLC
 * @license   http://code.google.com/p/openbiz-cubi/wiki/CubiLicense
 * @link      http://code.google.com/p/openbiz-cubi/
 * @version   $Id$
 */

class ModuleChangeLogForm extends EasyFormGrouping
{
	public function LoadAll()
	{
		include_once (MODULE_PATH."/system/lib/ModuleLoader.php");
		$_moduleArr = glob(MODULE_PATH."/*");
	    $moduleArr[0] = "system";
	    $moduleArr[1] = "menu";
		foreach ($_moduleArr as $_module)
		{
			$_module = basename($_module);	      
	        $moduleArr[] = $_module;	
		}
		
		foreach ($moduleArr as $moduleName)
		{
			$loader = new ModuleLoader($moduleName);
			$loader->loadChangeLog();
		}
		
		$this->updateForm();
	}
}
?>