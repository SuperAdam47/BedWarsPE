<?php

namespace BedWars;

use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\scheduler\PluginTask;
use pocketmine\plugin\Plugin;
use pocketmine\Player;
use pocketmine\item\Item; 
use pocketmine\tile\Tile;

class ExecuteTask extends PluginTask {
	public $callback = 0;
	public function __construct(Plugin $owner,$callback)
	{
		parent::__construct($owner);
		$this->callback = $callback;
	}
	public function onRun($currentTick) {
		call_user_func($this->callback);
	}
	public function cancel() {
		if ($this->getHandler() != null)
			$this->getHandler()->cancel();
	}
	static function Execute(Plugin $owner,$callback,$delay = 1,$mode = 0) {
		switch ($mode)
		{
			case 0: return $owner->getServer()->getScheduler()->scheduleDelayedTask(new ExecuteTask($owner,$callback),$delay);
			break;
			case 1: return $owner->getServer()->getScheduler()->scheduleRepeatingTask(new ExecuteTask($owner,$callback),$delay);
			break;
		}
	}
}
