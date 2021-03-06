<?php 
include_once (OPENBIZ_BIN."/easy/element/InputElement.php");
class DataSourceListbox extends InputElement{
    public $m_BlankOption;
   
    /**
     * Read metadata info from metadata array and store to class variable
     *
     * @param array $xmlArr metadata array
     * @return void
     */
    protected function readMetaData(&$xmlArr)
    {
        parent::readMetaData($xmlArr);
        $this->m_BlankOption = isset($xmlArr["ATTRIBUTES"]["BLANKOPTION"]) ? $xmlArr["ATTRIBUTES"]["BLANKOPTION"] : null;        
    }

    /**
     * Render, draw the control according to the mode
     *
     * @return string HTML text
     */
    public function render()
    {
        $fromList = array();
        $this->getFromList($fromList);
        $valueArray = explode(',', $this->m_Value);
        $disabledStr = ($this->getEnabled() == "N") ? "DISABLED=\"true\"" : "";
        $style = $this->getStyle();
        $func = $this->getFunction();

        //$sHTML = "<SELECT NAME=\"" . $this->m_Name . "[]\" ID=\"" . $this->m_Name ."\" $disabledStr $this->m_HTMLAttr $style $func>";
        $sHTML = "<SELECT NAME=\"" . $this->m_Name . "\" ID=\"" . $this->m_Name ."\" $disabledStr $this->m_HTMLAttr $style $func>";

        if ($this->m_BlankOption) // ADD a blank option
        {
            $entry = explode(",",$this->m_BlankOption);
            $text = $entry[0];
            $value = ($entry[1]!= "") ? $entry[1] : null;
            $entryList = array(array("val" => $value, "txt" => $text ));
            $fromList = array_merge($entryList, $fromList);
        }

        foreach ($fromList as $option)
        {
            $test = array_search($option['val'], $valueArray);
            if ($test === false)
            {
                $selectedStr = '';
            }
            else
            {
                $selectedStr = "SELECTED";
            }
            $sHTML .= "<OPTION VALUE=\"" . $option['val'] . "\" $selectedStr>" . $option['txt'] . "</OPTION>";
        }
        $sHTML .= "</SELECT>";
        /* editable combobox
        <div style="position: relative;">
        <select style="position: absolute; width: 146px; height: 18px; z-index: 1; clip: rect(auto, auto, auto, 127px);">
        <option value="" selected="selected"/>
        <option value="Homer">Homer</option>
        <option value="Marge">Marge</option>
        <option value="Bart">Bart</option>
        <option value="Lisa">Lisa</option>
        <option value="Maggie">Maggie</option>
        </select>
        <div>
        <input type="text" style="width: 128px; height: 20px;"/>
        </div>
        </div>
        */
        return $sHTML;
    }

 	public function getFromList(&$list)
    {
    	//get DB list from setting
		$dbobj 		= BizSystem::getObject('report.admin.do.ReportDbDO');
        $dbArr 		= $dbobj->directFetch();
        for($i=0;$i<count($dbArr);$i++){
        	$list[$i] = array('val'=>$dbArr[$i]['Id'],'txt'=>$dbArr[$i]['db_name']);
        }
    }    
    
  
}
?>