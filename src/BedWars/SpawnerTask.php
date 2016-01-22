<?php 

namespace BedWars;  

use pocketmine\math\Vector3; 
use pocketmine\scheduler\PluginTask; 
use pocketmine\plugin\Plugin;  
use pocketmine\Player;  
use pocketmine\item\Item; 
use pocketmine\block\Block; 
use pocketmine\tile\Tile; 
use pocketmine\tile\Chest; 
use pocketmine\nbt\tag\Byte; 
use pocketmine\nbt\tag\Compound; 
use pocketmine\nbt\tag\Double; 
use pocketmine\nbt\tag\Enum; 
use pocketmine\nbt\tag\Float; 
use pocketmine\nbt\tag\Int; 
use pocketmine\nbt\tag\Short; 
use pocketmine\nbt\tag\String;   

class SpawnerTask extends PluginTask {
	public $Game = 0,$Position = 0,$Type = 0;
	public $Mode = 0;
	protected $Item = null;
	public $status = 1;
	public function __construct(Plugin $owner,BedWarsGame $Game,Vector3 $Position,$Type) {
		parent::__construct($owner);
		$this->Game = $Game;
		$this->Position = $Position;
		switch ($Type)
		{
			case 'b': $this->Type = 336;
			break;
			case 'i': $this->Type = 265;
			break;
			case 'g': $this->Type = 266;
			break;
			default:  $this->Type = 1; 
			break;
		}
		if (($this->Mode = $owner->spawner_mode) == 0)
			{
				$this->Position->x = $this->Position->x + 0.5;
				$this->Position->z = $this->Position->z + 0.5;
			}
			elseif ($this->Mode == 1)
			{
				if ($this->Game->Level->getBlock($this->Position)->getId() != 54)
					$this->Game->Level->setBlock($this->Position,Block::get(54,0),true,true);
				if (!($chest = $this->Game->Level->getTile($this->Position)))
					{
						$chest = new Chest($this->Game->Level->getChunk($this->Position->getX() >> 4,$this->Position->getZ() >> 4,true),new Compound(false,array( new Int("x",$this->Position->getX()), new Int("y",$this->Position->getY()), new Int("z",$this->Position->getZ()), new String("id",Tile::CHEST))));
						$this->Game->Level->addTile($chest);
					}
			}
	}
	public function onRun($currentTick) {
		if ($this->status == 0)
			return;
		switch ($this->Mode)
		{
			case 0: $this->Game->Level->dropItem($this->Position,new Item($this->Type,0,1),new Vector3(0,0,0));
			break;
			case 1: if ($this->Game->Level->getBlock($this->Position)->getId() != 54)
				$this->Game->Level->setBlock($this->Position,Block::get(54,0),true,true);
			if (!($chest = $this->Game->Level->getTile($this->Position)))
				{
					$chest = new Chest($this->Game->Level->getChunk($this->Position->getX() >> 4,$this->Position->getZ() >> 4,true),new Compound(false,array( new Int("x",$this->Position->getX()), new Int("y",$this->Position->getY()), new Int("z",$this->Position->getZ()), new String("id",Tile::CHEST))));
					$this->Game->Level->addTile($chest);
				}
				$chest->getInventory()->addItem(new Item($this->Type,0,1));
				break;
		}
	}
	public function cancel() {
		if ($this->getHandler() != null)
			$this->getHandler()->cancel();
	}
}
