<?php 

namespace BedWars;  

use pocketmine\math\Vector3; 
use pocketmine\level\Position; 
use pocketmine\scheduler\PluginTask; 
use pocketmine\plugin\Plugin; 
use pocketmine\Player; 
use pocketmine\item\Item;  

class TPTask extends PluginTask {
	private $Target = 0,$Position = 0;
	public function __construct(Plugin $owner,Player $Target,Position $Position) {
		parent::__construct($owner);
		$this->Target = $Target;
		$this->Position = $Position;
	}
	public function onRun($currentTick) {
		$this->Target->teleport($this->Position);
	}
	public function cancel() {
		if ($this->getHandler() != null)
			$this->getHandler()->cancel();
	}
	static function TP(Plugin $owner,Player $Target,Position $Position,$delay = 1) {
		return $owner->getServer()->getScheduler()->scheduleDelayedTask(new TPTask($owner,$Target,$Position),$delay);
	}
}
