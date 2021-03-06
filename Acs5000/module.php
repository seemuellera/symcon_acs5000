<?php

    // Klassendefinition
    class Acs5000 extends IPSModule {
 
        // Der Konstruktor des Moduls
        // Überschreibt den Standard Kontruktor von IPS
        public function __construct($InstanceID) {
            // Diese Zeile nicht löschen
            parent::__construct($InstanceID);
 
            // Selbsterstellter Code
        }
 
        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() {
            
		// Diese Zeile nicht löschen.
            	parent::Create();

		// Properties
		$this->RegisterPropertyString("Sender","SymconAcs5000");
		$this->RegisterPropertyInteger("RefreshInterval",5);
		$this->RegisterPropertyInteger("SnmpInstance",0);
		$this->RegisterPropertyBoolean("DebugOutput",false);

		// Variables
		$this->RegisterVariableString("Hostname", "Hostname");
		$this->RegisterVariableString("DeviceType", "Device Type");
		$this->RegisterVariableString("FirmwareVersion", "Firmware Version");
		$this->RegisterVariableString("DeviceId", "Device ID");


		// Timer
		$this->RegisterTimer("RefreshInformation", 0, 'ACS5000_RefreshInformation($_IPS[\'TARGET\']);');
 
        }
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
    public function ApplyChanges() {

		$newInterval = $this->ReadPropertyInteger("RefreshInterval") * 1000;
		$this->SetTimerInterval("RefreshInformation", $newInterval);


		// Diese Zeile nicht löschen
		parent::ApplyChanges();
	}


	public function GetConfigurationForm() {

        	
		// Initialize the form
		$form = Array(
            		"elements" => Array(),
					"actions" => Array()
        		);

		// Add the Elements
		$form['elements'][] = Array("type" => "NumberSpinner", "name" => "RefreshInterval", "caption" => "Refresh Interval");
		$form['elements'][] = Array("type" => "CheckBox", "name" => "DebugOutput", "caption" => "Enable Debug Output");
		$form['elements'][] = Array("type" => "SelectInstance", "name" => "SnmpInstance", "caption" => "SNMP instance");

		// Add the buttons for the test center
        $form['actions'][] = Array("type" => "Button", "label" => "Refresh Overall Status", "onClick" => 'ACS5000_RefreshInformation($id);');

		// Return the completed form
		return json_encode($form);

	}


        /**
	* Get the list of robots linked to this profile and modifies the Select list to allow the user to select them.
        *
        */
    public function RefreshInformation() {

		$oid_mapping_table['Hostname'] = '.1.3.6.1.4.1.2925.8.2.1.0';
		$oid_mapping_table['DeviceType'] = '.1.3.6.1.4.1.2925.8.1.1.0';
		$oid_mapping_table['FirmwareVersion'] = '.1.3.6.1.4.1.2925.8.1.2.0';
		$oid_mapping_table['DeviceId'] = '.1.3.6.1.4.1.2925.8.1.8.0';

		$this->UpdateVariables($oid_mapping_table);
	}
	
	protected function LogMessage($message, $severity = 'INFO') {
		
		if ( ($severity == 'DEBUG') && ($this->ReadPropertyBoolean('DebugOutput') == false )) {
			
			return;
		}
		
		$messageComplete = $severity . " - " . $message;
		
		IPS_LogMessage($this->ReadPropertyString('Sender') . " - " . $this->InstanceID, $messageComplete);
	}
	
	protected function UpdateVariables($oids) {
	
		$result = $this->SnmpGet($oids);
		
		foreach ($oids as $varIdent => $varOid) {
		
			$this->UpdateVariable($varIdent, $result[$varOid]);
		}
	}

	protected function UpdateVariable($varIdent, $newValue) {
	
		$oldValue = GetValue($this->GetIDForIdent($varIdent));

		if ($newValue != $oldValue) {

			SetValue($this->GetIdForIdent($varIdent), $newValue);
		}
	}

	protected function SnmpGet($oids) {
	
		$result = IPSSNMP_ReadSNMP($this->ReadPropertyInteger("SnmpInstance"), $oids);
		
		$this->LogMessage("SNMP result " . print_r($result, true), "DEBUG");

		return $result;
	}

}