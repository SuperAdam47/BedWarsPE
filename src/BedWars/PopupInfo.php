<?php

namespace BedWars;  

use pocketmine\math\Vector3; 
use pocketmine\scheduler\PluginTask;
use pocketmine\plugin\Plugin; 
use pocketmine\Player; 
use pocketmine\item\Item; 
use pocketmine\level\Level;  

class PopupInfoTask extends PluginTask {
	public $Level = 0,$PopupInfo = 0,$Mode = 0;
	public $Status = 1;
	public $owner = 0;
	
	public function __construct(Plugin $owner,$Level,PopupInfo $PopupInfo,$Mode) {
		parent::__construct($owner);
		$this->owner = $owner;
		$this->Level = $Level;
		$this->PopupInfo = $PopupInfo;
		$this->Mode = $Mode;
	}
	public function onRun($currentTick) {
		if ($this->Status) {
			$owner = $this->owner;
			$Players = ($this->Level == null) ? call_user_func(function() use ($owner)
			{
				$Server = $owner->getServer();
				$Levels = $Server->getLevels();
				$Players = [];
				foreach ($Levels as $i => $Level)
				{
					$Players = array_merge($Players,$Level->getPlayers());
				}
				return $Players;
			}) : $this->Level->getPlayers();
			if ($this->PopupInfo->PlayersData)
				{
					foreach ($Players as $i => $Player) 
					{
						if (!isset($this->PopupInfo->PlayersData[strtolower($Player->getName())]))
							continue;
						switch ($this->Mode)
						{
							case 0: $Player->sendTip(implode("\n",$this->PopupInfo->PlayersData[strtolower($Player->getName())]));
							break;
							case 1: $Player->sendPopup(implode("\n",$this->PopupInfo->PlayersData[strtolower($Player->getName())]));
							break;
						}
					}
					} else {
						foreach ($Players as $i => $Player)
						{
							switch ($this->Mode)
							{
								case 0: $Player->sendTip(implode("\n",$this->PopupInfo->Rows));
								break;
								case 1: $Player->sendPopup(implode("\n",$this->PopupInfo->Rows));
								break;
							}
						}
					}
		}
	}
	public function cancel() {
		if ($this->getHandler() != null)
			$this->getHandler()->cancel();
	}
}
	class PopupInfo {
		public $Rows = Array();
		public $PlayersData = 0;
		private $Task = 0;
		public function __construct(Plugin $owner,$Level,$Mode) {
			$this->Task = new PopupInfoTask($owner,$Level,$this,$Mode);
			$owner->getServer()->getScheduler()->scheduleRepeatingTask($this->Task,7);
		}
		public function resume() {
			$this->Task->Status = 1;
		}
		public function stop() {
			$this->Task->Status = 0;
		}
		public function cancel() {
			$this->Task->cancel();
		}
	}
