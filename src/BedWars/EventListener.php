<?php
namespace BedWars; 

use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\Villager;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\tile\Tile;
use pocketmine\tile\Chest;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\ChestInventory;
use pocketmine\inventory\CustomInventory;
use pocketmine\inventory\InventoryType;
use pocketmine\inventory\BaseTransaction;
use pocketmine\nbt\tag\Byte;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Double;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\Float
use pocketmine\nbt\tag\Int; 
use pocketmine\nbt\tag\Short;
use pocketmine\nbt\tag\String; 
use pocketmine\event\Listener; 
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\inventory\InventoryOpenEvent; 
use pocketmine\event\inventory\InventoryTransactionEvent; 
use pocketmine\event\inventory\InventoryCloseEvent; 
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerDeathEvent; 
use pocketmine\event\player\PlayerJoinEvent; 
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerPreLoginEvent; 
use pocketmine\event\player\PlayerQuitEvent; 
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerGameModeChangeEvent;
use pocketmine\event\player\PlayerChatEvent; 
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\utils\TextFormat; 
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityInventoryChangeEvent;
use pocketmine\event\entity\EntityTeleportEvent; 
use pocketmine\event\entity\EntityDespawnEvent;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityExplodeEvent; 

class EventListener implements Listener {
	private $plugin;
	private $PlayerBuying = Array();
	public $Chests = Array();
	
	public function __construct(BedWars $plugin) {
		$this->plugin = $plugin;
	}
	public function onPlayerJoin(PlayerJoinEvent $event) {
		$Player = $event->getPlayer();
		$Level = $Player->getLevel();
		if (file_exists($this->plugin->getDataFolder()."levels/".$Level->getFolderName().".yml"))
			{
				$Player->teleport($this->plugin->lobby->getSafeSpawn());
				$Player->teleport($this->plugin->lobby_spawn);
				$Player->setGamemode(Player::SURVIVAL);
			}
			elseif ($Level->getFolderName() == $this->plugin->lobby_name)
			{
				$Player->setGamemode(Player::SURVIVAL);
			}
	}
	public function onPlayerQuit(PlayerQuitEvent $event) {
		$Player = $event->getPlayer();
		$Level = $Player->getLevel();
		if ((!$this->plugin->game) || ($this->plugin->game->level_name != $Level->getFolderName()))
			return;
		$Player->setNameTag($Player->getName());
		if ($Team = $this->plugin->game->getTeamByPlayer($Player))
			{
				array_splice($Team->Players,array_search($Player,$Team->Players),1);
				$this->plugin->game->updateTeams();
			}
			$Player->removeAllEffects();
			$Player->setDataFlag(Entity::DATA_FLAGS,Entity::DATA_FLAG_INVISIBLE,false);
	}
	public function onPlayerKick(PlayerKickEvent $event)
	{
		$Player = $event->getPlayer();
		$Level = $Player->getLevel();
		if ((!$this->plugin->game) || ($this->plugin->game->level_name != $Level->getFolderName()))
			return;
		if (strpos($event->getReason(),"flying") !== false)
			{
				$event->setCancelled(true);
				return;
			}
			$Player->setNameTag($Player->getName());
			if ($Team = $this->plugin->game->getTeamByPlayer($Player))
				{
					array_splice($Team->Players,array_search($Player,$Team->Players),1);
					$this->plugin->game->updateTeams();
				}
				$Player->removeAllEffects();
				$Player->setDataFlag(Entity::DATA_FLAGS,Entity::DATA_FLAG_INVISIBLE,false);
	}
	public function onPlayerDeath(PlayerDeathEvent $event) {
		$Player = $event->getEntity();
		$Level = $Player->getLevel();
		if ((!$this->plugin->game) || ($this->plugin->game->level_name != $Level->getFolderName()))
			return; 
		$event->setDrops(Array());
		if ($Team = $this->plugin->game->getTeamByPlayer($Player))
			{
				$this->plugin->game->PopupInfo2->PlayersData[strtolower($Player->getName())][1] = 0;
				if ($Team->BedStatus == 0)
				{
					if (($Idx = array_search($Player,$Team->Players)) !== false)
						{
							array_splice($Team->Players,$Idx,1);
							$this->plugin->game->updateTeams();
						}
				}
			}
	}
	public function onPlayerRespawn(PlayerRespawnEvent $event) {
		$Player = $event->getPlayer();
		$Level = $Player->getLevel();
		if ((!$this->plugin->game) || ($this->plugin->game->level_name != $Level->getFolderName()))
		return;
		$this->plugin->game->Spawn($event);
	}
	public function onPlayerMove(PlayerMoveEvent $event) {
		$Player = $event->getPlayer();
		$Level = $Player->getLevel();
		if ((!$this->plugin->game) || ($this->plugin->game->level_name != $Level->getFolderName()))
			return;
		if $Player->getGamemode() == Player::SPECTATOR)
		return;
		$event->setCancelled($this->plugin->game->PlayerMove($event->getPlayer(),$event->getFrom(),$event->getTo()));
		$X = $Player->getFloorX();
		$Y = intval($Player->getY());
		$Z = $Player->getFloorZ();
		$Block = $Level->getBlock(new Vector3($X,$Y,$Z));
		if ($Block->getId() == 133)
		{
			$Player->setMotion(new Vector3(0,0,0));
			}
			elseif (($Block->getId() == 44) && (($Block->getDamage() % 8) == 4))
			{
				$Player->setMotion(new Vector3(rand(-50,50) / 50,rand(100,250) / 100,rand(-50,50) / 50));
				$Level->setBlock(new Vector3($X,$Y,$Z),Block::get(0),true,true);
			}
			elseif (($Block->getId() == 96) && ($Block->getDamage() < 4))
			{
				$Level->getBlock(new Vector3($X,$Y,$Z))->onActivate(Item::get(0));
			}
	}
	public function onPlayerChat(PlayerChatEvent $event) {
		$Player = $event->getPlayer();
		$Level = $Player->getLevel();
		$Message = $event->getMessage();
		if ((!$this->plugin->game) || ($this->plugin->game->level_name != $Level->getFolderName()))
		return;
		if ($Team = $this->plugin->game->getTeamByPlayer($Player))
		{
			$event->setFormat("BedWars: <".$this->plugin->teamColor($Team->name)."%s".TextFormat::RESET.">: %s");
			$prefix = mb_strtolower($this->plugin->getMessage("bedwars.sayall.prefix"));
			if (mb_substr(mb_strtolower($Message),0,mb_strlen($prefix)) == $prefix)
				{
					$event->setMessage(mb_substr($Message,mb_strlen($prefix)));
					return;
				}
				$Players = $Level->getPlayers();
				foreach ($this->plugin->game->Teams as $Team2)
				if ($Team->name != $Team2->name)
					foreach ($Team2->Players as $Player2)
				foreach ($Players as $i => $Player3)
				if ($Player2->getName() == $Player3->getName())
					array_splice($Players,$i,1);
				$event->setRecipients($Players);
		}
	}
	public function onPlayerItemHeld(PlayerItemHeldEvent $event) {
		$Player = $event->getPlayer();
		$Level = $Player->getLevel();
		if ((!$this->plugin->game) || ($this->plugin->game->level_name != $Level->getFolderName()))
			return;
		$HandItem = $event->getItem();
		$X = $Player->getFloorX();
		$Y = $Player->getFloorY() - 1;
		$Z = $Player->getFloorZ();
		$Block = $Level->getBlock(new Vector3($X,$Y,$Z));
		if (($HandItem->getId() == 341) && ($Block->getId() == 0))
			{
				$SlimeX = $X; $SlimeY = $Y - 2;
				$SlimeZ = $Z;
				for ($x = $SlimeX - 2; $x <= $SlimeX + 2; $x++)
					for ($z = $SlimeZ - 2; $z <= $SlimeZ + 2; $z++)
						if ($Level->getBlockIdAt($x,$SlimeY,$z) == 0)
							{
								$Level->setBlockIdAt($x,$SlimeY,$z,133);
								$this->plugin->game->BlocksPlaced[] = new Vector3($x,$SlimeY,$z);
							}
							$HandItem->setCount($HandItem->getCount() - 1);
							$Player->getInventory()->setItemInHand($HandItem);
							$Player->setMotion(new Vector3(0,0.2,0));
			}
	}
	public function onPlayerGameModeChange(PlayerGameModeChangeEvent $event) {
		$Player = $event->getPlayer();
		$Level = $Player->getLevel();
		if ((!$this->plugin->game) || ($this->plugin->game->level_name != $Level->getFolderName()))
			return;
		$event->setCancelled(!in_array($event->getNewGamemode(),[0,3]));
	}
	public function onPlayerBlockTouch(PlayerInteractEvent $event) {
		$Player = $event->getPlayer();
		$Level = $Player->getLevel();
		$Item = $event->getItem();
		if ((!$this->plugin->game) || ($this->plugin->game->level_name != $Level->getFolderName()))
			return;
		if ($Item->getId() == 259) { $event->setCancelled(true);
		return;
		}
		if ($event->getFace() == 255)
			{
				$Inv = $Player->getInventory();
				$ID = $Item->getId();
				if (($ID >= 298) && ($ID <= 314))
					{
						$ID = ($ID - 298) % 4;
						$Armor = $Inv->getArmorItem($ID);
						$Inv->setArmorItem($ID,$Item);
						$Inv->setItemInHand($Armor);
					}
					elseif ($ID == 37)
					{
						$this->plugin->game->PopupInfo2->PlayersData[strtolower($Player->getName())][1] += 50;
						$ItemInHand = $Inv->getItemInHand();
						$ItemInHand->setCount($ItemInHand->getCount() - 1);
						$Inv->setItemInHand($ItemInHand);
					}
					elseif ($ID == 38)
					{
						$this->plugin->game->PopupInfo2->PlayersData[strtolower($Player->getName())][1] += 100;
						$ItemInHand = $Inv->getItemInHand();
						$ItemInHand->setCount($ItemInHand->getCount() - 1);
						$Inv->setItemInHand($ItemInHand);
					}
					elseif ($ID == 345)
					{
						if ($Team = $this->plugin->game->getTeamByPlayer($Player))
							{
								$this->plugin->setState("teleport",$Player,false);
								$Player->teleport($Team->Spawn);
								$ItemInHand = $Inv->getItemInHand();
								$ItemInHand->setCount($ItemInHand->getCount() - 1);
								$Inv->setItemInHand($ItemInHand);
							}
					}
					return;
			}
			$Block = $event->getBlock();
			if ($Block->getId() == 68)
				$event->setCancelled($this->plugin->game->SignClick($Block->getX(),$Block->getY(),$Block->getZ(),$event->getPlayer()));
			else $event->setCancelled($this->plugin->game->BlockClick($Block->getX(),$Block->getY(),$Block->getZ(),$Block,$Player));
	}
	public function onEntityTeleport(EntityTeleportEvent $event)     {
		$Player = $event->getEntity();
		$From = $event->getFrom();
		$To = $event->getTo();
		$Level = $Player->getLevel();
		$Level2 = $From->getLevel();
		$Level3 = $To->getLevel();
		if (($Level2->getFolderName() != $Level3->getFolderName()) && (($this->plugin->game->level_name == $Level2->getFolderName()) || ($this->plugin->game->level_name == $Level3->getFolderName())))
		{
			$event->setCancelled($this->plugin->getState("teleport",$Player,true));
			$this->plugin->setState("teleport",$Player,true);
			return;
		}
		if ((!$this->plugin->game) || ($this->plugin->game->level_name != $Level->getFolderName()) || ($this->plugin->game->Status == 0))
			return;
		if ($Player instanceof Player)
			{
				if ($Team = $this->plugin->game->getTeamByPlayer($Player))
					{
						$event->setCancelled($this->plugin->getState("teleport",$Player,true));
						$this->plugin->setState("teleport",$Player,true);
					}
			}
	}
	public function onEntityExplode(EntityExplodeEvent $event) {
		$Entity = $event->getEntity();
		$Level = $Entity->getLevel();
		if ((!$this->plugin->game) || ($this->plugin->game->level_name != $Level->getFolderName()))
			return;
		$Blocks = $event->getBlockList();
		$Blocks2 = [];
		foreach ($Blocks as $Block)
		foreach ($this->plugin->game->BlocksPlaced as $Block2)
		if (($Block->getX() == $Block2->getX()) && ($Block->getY() == $Block2->getY()) && ($Block->getZ() == $Block2->getZ()))
			{
				$Blocks2 []= $Block;
				continue;
			}
			$event->setBlockList($Blocks2);
	}
	public function onEntityDamage(EntityDamageEvent $event) {
		if ($event instanceof EntityDamageByEntityEvent)
			{
				if (($Victim = $event->getEntity()) && ($Player = $event->getDamager()))
					{
						if ($Player instanceof Human)
							{
								$Level = $Player->getLevel();
								if ($Victim instanceof Villager)
									{
										$Type = ($Level->getFolderName() == $this->plugin->lobby_name) ? 1 : 0;
										$X = round($Victim->getX() - 0.5);
										$Z = round($Victim->getZ() - 0.5);
										if ($Level->getBlockIdAt($X,0,$Z) != 54)
											{
												$Level->setBlock(new Vector3($X,0,$Z),Block::get(54),true,true);
												$chest = new Chest($Level->getChunk($X >> 4,$Z >> 4,true),new Compound(false,array( new Int("x",$X), new Int("y",0), new Int("z",$Z), new String("id",Tile::CHEST))),$this->plugin);
												$Level->addTile($chest);
											}
											else $chest = $Level->getTile(new Vector3($X,0,$Z));
											if ($Level->getBlockIdAt($X,1,$Z) != 54)
												$Level->setBlock(new Vector3($X,1,$Z),Block::get(0),true,true);
											$chest = new BuyingInventory($Level->getTile(new Vector3($X,0,$Z)),$Player);
											$contents = [];
											switch ($Type)
											{
												case 0: foreach ($this->plugin->Buys_Values as $Buy_Value)
												$contents []= Item::get($Buy_Value[0],0,1);
												break;
												case 1: if ($this->plugin->Status == 0)
													{
														foreach ($this->plugin->MapsList as $Map)
														$contents []= Item::get(35,$Map[0],($Map[4] == 0) ? 99 : $Map[4]);
														} else {
															$contents []= Item::get(345,0,1);
															foreach ($this->plugin->game->Teams as $Name => $Team)
															$contents []= Item::get(35,$this->plugin->getTeamDataByName($Name),(count($Team->Players) == 0) ? 99 : count($Team->Players));
														}
														break;
											}
											$chest->setContents($contents);
											$this->plugin->setState("buying_chest",$Player,$chest);
											$this->plugin->setState("buying_type",$Player,$Type);
											$this->plugin->setState("buying_menu",$Player,-1);
											$Player->addWindow($chest);
											$event->setCancelled(true);
									}
									elseif ($Victim instanceof Human)
									{
										if ($Level->getFolderName() == $this->plugin->lobby_name)
											{
												$event->setCancelled(true);
												return;
											}
											if ((!$this->plugin->game) || ($this->plugin->game->level_name != $Level->getFolderName())) return;
											$event->setCancelled($this->plugin->game->getTeamByPlayer($Player) == $this->plugin->game->getTeamByPlayer($Victim));
											$HandItem = $Player->getInventory()->getItemInHand();
											switch ($HandItem->getId())
											{
												case 259: $Victim->setOnFire(5);
												$event->setKnockBack(0);
												break;
												case 280: $event->setKnockBack(0.6);
												break;
											}
											if ($Victim->getHealth() - $event->getFinalDamage() <= 0)
												{
													$this->plugin->game->PopupInfo2->PlayersData[strtolower($Player->getName())][1] += $this->plugin->game->PopupInfo2->PlayersData[strtolower($Victim->getName())][1];
													$this->plugin->game->PopupInfo2->PlayersData[strtolower($Victim->getName())][1] = 0;
												}
									}
									elseif ($Victim instanceof Villager)
									{
										$Level = $Victim->getLevel();
										if ($Level->getFolderName() == $this->plugin->lobby_name)
											{
												$event->setCancelled(true);
												return;
											}
											if ((!$this->plugin->game) || ($this->plugin->game->level_name != $Level->getFolderName()))
												return;
											$event->setCancelled(true);
									}
							}
					}
			}
			else if (($Player = $event->getEntity()) instanceof Entity)
				{
					$Level = $Player->getLevel();
					if ((!$this->plugin->game) || ($this->plugin->game->level_name != $Level->getFolderName()))
						return;
					$X = $Player->getFloorX();
					$Y = $Player->getFloorY() - 1;
					$Z = $Player->getFloorZ();
					$Block = $Level->getBlock(new Vector3($X,$Y,$Z));
					if ($Block->getId() == 133)
						{
							$event->setCancelled(true);
						}
				}
	}
	public function onEntityDespawn(EntityDespawnEvent $event) {
		$Entity = $event->getEntity();
		$Level = $Entity->getLevel();
		if ((!$this->plugin->game) || ($this->plugin->game->level_name != $Level->getFolderName()))
			return;
		if ($event->getType() === 81)
			{
				$ballid = $Entity->getId();
				$shooter = $Entity->shootingEntity;
				$posTo = $Entity->getPosition();
				if ($posTo->getY() < 10) return;
				if ($posTo instanceof Position)
					{
						if ($shooter instanceof Player)
							{
								$posFrom = $shooter->getPosition();
								$this->plugin->setState("teleport",$shooter,false);
								$shooter->teleport($posTo);
							}
					}
			}
	}
	public function onBlockBreak(BlockBreakEvent $event) {
		$Player = $event->getPlayer();
		$Level = $Player->getLevel();
		$Block = $event->getBlock();
		if ($Level->getFolderName() == $this->plugin->lobby_name)
			{
				$event->setCancelled(!(($Player->isOp()) && ($Player->getGamemode() == Player::CREATIVE)));
				return;
			}
			if ((!$this->plugin->game) || ($this->plugin->game->level_name != $Level->getFolderName()))
				return;
			if ($this->plugin->game->DestroyBlock($Block,$Player))
				$event->setCancelled(true);
	}
	public function onBlockPlace(BlockPlaceEvent $event) {
		$Player = $event->getPlayer();
		$Level = $Player->getLevel();
		$Block = $event->getBlock();
		if ($Level->getFolderName() == $this->plugin->lobby_name)
			{
				$event->setCancelled(!(($Player->isOp()) && ($Player->getGamemode() == Player::CREATIVE)));
				return;
			}
			if ((!$this->plugin->game) || ($this->plugin->game->level_name != $Level->getFolderName()))
				return;
			$event->setCancelled($this->plugin->game->PlaceBlock($Block,$Player));
	}
	public function onBlockUpdate(BlockUpdateEvent $event) {
		/*$Block = $event->getBlock();
		$Level = $Block->getLevel();
		if ((!$this->plugin->game) || ($this->plugin->game->level_name != $Level->getFolderName()))
			return;
		if ($Level->getBlock($Block)->getId() == 0) return;
		foreach ($this->plugin->game->BlocksPlaced as $Block2)
		if ($Block->equal($Block2))
			return;
		$event->setCancelled(true);*/
	}
	public function onInventoryOpen(InventoryOpenEvent $event) { }
	public function onTransaction(InventoryTransactionEvent $event){
		$Transaction = $event->getTransaction();
		$Player = null;
		$BuyingTile = null;
		$BuyingInv = null;
		foreach ($Transaction->getInventories() as $inv)
		{
			if ($inv instanceof PlayerInventory)
				$Player = $inv->getHolder();
			elseif (($inv instanceof BuyingInventory) || ($inv instanceof ChestInventory))
			$BuyingInv = $inv;
		}
		if ((!$Player) || (!$BuyingInv))
			return;
		if ($this->plugin->getState("buying_chest",$Player,null) == null) 
			return;
		$Level = $Player->getLevel();
		$BuyingTile = $BuyingInv->getHolder();
		$added = [];
		foreach ($Transaction->getTransactions() as $t)
		{
			foreach ($this->traderInvTransaction($t) as $nt)
			$added []= $nt;
		}
		$event->setCancelled(true);
		$X = $BuyingTile->getX();
		$Y = $BuyingTile->getY();
		$Z = $BuyingTile->getZ();
		$Slot = $added[0]->getSlot();
		$SourceItem = $added[0]->getSourceItem();
		$TargetItem = $added[0]->getTargetItem();
		switch ($this->plugin->getState("buying_type",$Player,null))
		{
			case 0: if ($Y == 0)
				{
					if ($Level->getBlockIdAt($X,$Slot + 1,$Z) != 54)
						{
							$Level->setBlock(new Vector3($X,$Slot + 1,$Z),Block::get(54),true,true);
							$chest = new Chest($Level->getChunk($X >> 4,$Z >> 4,true),new Compound(false,array( new Int("x",$X), new Int("y",$Y + 1), new Int("z",$Z), new String("id",Tile::CHEST))),$this->plugin);
							$Level->addTile($chest);
						}
						else $chest = $Level->getTile(new Vector3($X,$Y + 1,$Z));
						if ($Level->getBlockIdAt($X,$Slot + 2,$Z) != 54)
							$Level->setBlock(new Vector3($X,$Slot + 2,$Z),Block::get(0),true,true);
						$chest = new BuyingInventory($chest,$Player);
						$contents = [];
						foreach ($this->plugin->Buys_Values[$Slot][1] as $j => $buy)
						$contents []= Item::get($buy[1],$buy[2],$buy[3]);
						$chest->setContents($contents);
						$this->plugin->setState("buying_chest",$Player,$chest);
						$this->plugin->setState("buying_menu",$Player,$Slot);
						$Player->addWindow($chest);
						} else {
							$this->plugin->game->Buy($Player,$this->plugin->getState("buying_menu",$Player,-1),$Slot);
						}
						break;
						case 1: $this->plugin->SelectTeam($Player,$Slot - 1,$TargetItem);
						break;
						case 2: $Item = $BuyingInv->getItem($Slot);
						if ((($i = array_search($Item->getId(),[336,265,266])) !== false) && ($t = $this->plugin->spawner_gives[["b","i","g"][$i]]))
							{
								if (!isset($this->plugin->game->PopupInfo2->PlayersData[strtolower($Player->getName())][1]))
									{
										$this->plugin->game->PopupInfo2->PlayersData[strtolower($Player->getName())][0] = TextFormat::GREEN; $this->plugin->game->PopupInfo2->PlayersData[strtolower($Player->getName())][1] = 0;
									}
									$this->plugin->game->PopupInfo2->PlayersData[strtolower($Player->getName())][1] += $Item->getCount() * $t;
									$BuyingInv->clear($Slot);
							}
							else $event->setCancelled(false);
							break;
		}
	}
	public function onOpen(InventoryOpenEvent $event) {
		$Player = $event->getPlayer();
		$Level = $Player->getLevel();
		$Inventory = $event->getInventory();
		$Holder = $Inventory->getHolder();
		if ((!$this->plugin->game) || ($this->plugin->game->level_name != $Level->getFolderName()))
			return;
		$X = $Holder->getX();
		$Y = $Holder->getY();
		$Z = $Holder->getZ();
		foreach ($this->plugin->game->LevelData["spawners"] as $spawner)
		{
			$spawner = explode(" ",$spawner);
			$type = $spawner[0];
			$x = $spawner[1];
			$y = $spawner[2];
			$z = $spawner[3];
			if (($x == $X) && ($y == $Y) && ($z == $Z))
				{
					$this->plugin->setState("buying_chest",$Player,$Inventory);
					$this->plugin->setState("buying_type",$Player,2);
				}
		}
	}
	public function onClose(InventoryCloseEvent $event) {
		$Player = $event->getPlayer();
		$this->plugin->unsetState("buying_chest",$Player);
	}
	public function playerInvTransaction($t) {
		$src = clone $t->getSourceItem();
		$dst = clone $t->getTargetItem();
		if (($dst->getCount() == 0) || ($dst->getId() == Item::AIR))
			return [];
		$srccnt = ($src->getId() == Item::AIR) ? 0 : $src->getCount();
		$dstcnt = ($dst->getId() == Item::AIR) ? 0 : $dst->getCount();
		if (($srccnt == $dstcnt) && ($src->getId()) == ($dst->getId()))
			return [];
		$idmeta = implode(":",[$dst->getId(),$dst->getDamage()]);
		$Player = $t->getInventory()->getHolder();
		$xx = $this->plugin->getState("buying_chest",$Player,null);
		if ($xx == null) return [];
		return [ new BaseTransaction($t->getInventory(),$t->getSlot(),clone $t->getTargetItem(),clone $dst) ];
	}
	public function traderInvTransaction($t) {
		$src = clone $t->getSourceItem();
		$dst = clone $t->getTargetItem();
		if ($dst->getId() == Item::AIR)
			return [ new BaseTransaction($t->getInventory(),$t->getSlot(),clone $t->getTargetItem(),clone $src) ];
		if ($src->getId() == Item::AIR)
			return [ new BaseTransaction($t->getInventory(),$t->getSlot(),clone $dst,clone $src) ];
		if ($dst->getCount() > 1) { $dst->setCount(1);
		return [ new BaseTransaction($t->getInventory(),$t->getSlot(),clone $t->getTargetItem(),clone $dst) ];
		}
		return [];
	}
	public function onInventoryChange(EntityInventoryChangeEvent $event) {
		if (($this->plugin->game) && ($Player = $event->getEntity()) && ($Player instanceof Human) && ($Level = $Player->getLevel()) && ($Player->getGamemode() == 0))
			{
				$OldItem = $event->getOldItem();
				$NewItem = $event->getNewItem();
				if (($this->plugin->game->level_name != $Level->getFolderName()))
					return;
				switch ($NewItem->getId())
				{
					case 336: $this->plugin->game->PopupInfo2->PlayersData[strtolower($Player->getName())][1] += $NewItem->getCount() * $this->plugin->spawner_gives["b"];
					$event->setCancelled(true);
					break;
					case 265: $this->plugin->game->PopupInfo2->PlayersData[strtolower($Player->getName())][1] += $NewItem->getCount() * $this->plugin->spawner_gives["i"];
					$event->setCancelled(true);
					break;
					case 266: $this->plugin->game->PopupInfo2->PlayersData[strtolower($Player->getName())][1] += $NewItem->getCount() * $this->plugin->spawner_gives["g"];
					$event->setCancelled(true);
					break;
				}
			}
	}
}
