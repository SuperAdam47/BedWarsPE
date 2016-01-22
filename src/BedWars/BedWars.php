<?php

 namespace BedWars; 
 
 //PM use
use pocketmine\math\Vector3;
use pocketmine\block\Block;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\IPlayer;
use pocketmine\utils\Config;
use pocketmine\permission\PermissionAttachment;
use pocketmine\permission\Permission;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\item\Item;
use pocketmine\tile\Tile;
use pocketmine\tile\Sign;
use pocketmine\tile\Chest;
use pocketmine\nbt\tag\Byte;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Double;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\Float;
use pocketmine\nbt\tag\Int;
use pocketmine\nbt\tag\Short;
use pocketmine\nbt\tag\String;
use pocketmine\event\player\PlayerRespawnEvent;
//BedWars use
use BedWars\EventListener;
use BedWars\PopupInfo;
use BedWars\TPTask;
use BedWars\ExecuteTask;
//Shop
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\ChestInventory;
use pocketmine\inventory\CustomInventory;
use pocketmine\inventory\InventoryType;
use pocketmine\entity\Entity;
use pocketmine\entity\Villager;
use pocketmine\entity\Item as EntityItem;
use pocketmine\entity\Effect;

class BuyingInventory extends CustomInventory{
	 protected $client;
         public function __construct($holder,$client){
		 $this->client = $client;
		 parent::__construct($holder,InventoryType::get(InventoryType::CHEST),[],null,"");
		 }
		 public function getClient() {
			 return $this->client; } }
			 class BedWarsGameTeam {
				 public $name = 0;
				 public $Bed = 0;
				 public $Spawn = 0;
				 public $Players = 0;
				 public $BedStatus = 1;
				 function __construct($name = "",$Bed = null,$Spawn = null) {
					 $this->name = $name;
					 $this->Bed = is_null($Bed) ? Array(new Vector3(0,0,0),new Vector3(0,0,0),0) : $Bed; $this->Spawn = is_null($Spawn) ? new Vector3(0,0,0) : $Spawn; $this->Players = Array();
					 $this->BedStatus = 1; } };
					 class BedWarsGame {
						 public $BlocksPlaced = Array();
						 public $Level = 0;
						 public $Lobby = 0;
						 public $plugin = 0;
						 public $LevelData = 0;
						 public $Teams = Array();
						 public $Status = 0;
						 public $SpawnTasks = Array();
						 public $PopupInfo = 0,$PopupInfo2 = 0;
						 function __construct(Level $Level,BedWars $plugin) {
							 $this->Level = $Level;
							 $this->plugin = $plugin;
							 $this->LevelData = (new Config($plugin->getDataFolder()."levels/".$Level->getFolderName().".yml"))->getAll();
							 $this->level_name = $Level->getFolderName();
							 $this->PopupInfo = new PopupInfo($this->plugin,$Level,1);
							 $this->PopupInfo->Rows = Array();
							 foreach ($this->LevelData["teams"] as $name => $team) {
								 /*$this->PopupInfo->Rows[$name] = "[".$this->plugin->teamColorName($name)."] = 0";*/ };
								 $this->PopupInfo2 = new PopupInfo($this->plugin,$Level,0);
								 $this->PopupInfo2->PlayersData = Array();
								 $Level->setAutoSave(false);
								 $Level->setTime(6000);
								 $Level->stopTime();
								 $this->initBlocks();}
								 public function initBlocks() {
									 foreach ($this->LevelData["teams"] as $name => $Team) {
										 $Bed = explode(" ",$Team["bed"]);
										 $Spawn = explode(" ",$Team["spawn"]);
										 $Team = $this->Teams[$name] = new BedWarsGameTeam($name,Array(new Vector3($Bed[0],$Bed[1],$Bed[2]),new Vector3($Bed[3],$Bed[4],$Bed[5]),$Bed[6]),new Vector3($Spawn[0] - 0.5,$Spawn[1],$Spawn[2] + 0.5));
										 $this->Level->setBlock(new Position($Bed[0],$Bed[1],$Bed[2]),Block::get(26,8 + $Bed[6]),false,true);
										 $this->Level->setBlock(new Position($Bed[3],$Bed[4],$Bed[5]),Block::get(26,    $Bed[6]),false,true); };
										 foreach ($this->LevelData["spawners"] as $i => $spawner) {
											 $spawner = explode(" ",$spawner);
											 $type = $spawner[0]; $x = $spawner[1]; $y = $spawner[2]; $z = $spawner[3]; $pos = new Vector3($x,$y,$z);
											 switch ($this->plugin->spawner_mode) {
											 case 0: $this->Level->setBlock($pos,Block::get(0,0),true,true);
											 break;
											 case 1: $this->Level->setBlock($pos,Block::get(54,0),true,true);
											 $chest = new Chest($this->Level->getChunk($pos->getX() >> 4,$pos->getZ() >> 4,true),new Compound(false,array( new Int("x",$pos->getX()), new Int("y",$pos->getY()), new Int("z",$pos->getZ()), new String("id",Tile::CHEST))));
											 $this->Level->addTile($chest);
											 break;}
										 }
										 foreach ($this->Level->getEntities() as $Entity) 
										 if ($Entity instanceof Villager) {
											 for ($i = 0; $i < 10; $i++) {
												 $X = round($Entity->getX() - 0.5); 
												 $Z = round($Entity->getZ() - 0.5);
												 if ($this->Level->getBlockIdAt($X,0,$Z) != 54) {
													 $this->Level->setBlock(new Vector3($X,$i,$Z),Block::get(54),true,true);
													 $chest = new Chest($this->Level->getChunk($X >> 4,$Z >> 4,true),new Compound(false,array( new Int("x",$X), new Int("y",$i), new Int("z",$Z), new String("id",Tile::CHEST))),$this->plugin);
													 $this->Level->addTile($chest);
													 }
													 if ($this->Level->getBlockIdAt($X,$i + 1,$Z) != 54) 
														 $this->Level->setBlock(new Vector3($X,$i + 1,$Z),Block::get(0),true,true);
													 }
										 }
								 }
								 public function PlaceBlock(Block $Block,Player $Placer) {
									 if (in_array($Block->getId(),[ 51 ]))
										 return true;
									 $this->BlocksPlaced[] = new Vector3($Block->getX(),$Block->getY(),$Block->getZ());
								 return false;} 
								 public function DestroyBlock(Block $Block,Player $Destroyer) {
									 $X = $Block->GetX();
									 $Y = $Block->GetY();
									 $Z = $Block->GetZ();
									 if ($Team = $this->getTeamByPlayer($Destroyer)) {
										 if ((($X == $Team->Bed[0]->getX()) && ($Y == $Team->Bed[0]->getY()) && ($Z == $Team->Bed[0]->getZ())) ||  (($X == $Team->Bed[1]->getX()) && ($Y == $Team->Bed[1]->getY()) && ($Z == $Team->Bed[1]->getZ())))
											 return true;
										 }
										 foreach ($this->Teams as $name => $Team) {
											 if ((($X == $Team->Bed[0]->getX()) && ($Y == $Team->Bed[0]->getY()) && ($Z == $Team->Bed[0]->getZ())) ||  (($X == $Team->Bed[1]->getX()) && ($Y == $Team->Bed[1]->getY()) && ($Z == $Team->Bed[1]->getZ()))) {
												 $Team->BedStatus = 0; $this->sendMessageToAll(TextFormat::RED.$this->plugin->getMessage("bedwars.team.destroyed",$name));
												 $this->updateTeams();
												 $this->Level->setBlock(new Vector3($Team->Bed[0]->getX(),$Team->Bed[0]->getY(),$Team->Bed[0]->getZ()),Block::get(0),true,true);
												 $this->Level->setBlock(new Vector3($Team->Bed[1]->getX(),$Team->Bed[1]->getY(),$Team->Bed[1]->getZ()),Block::get(0),true,true);
												 return false;
												 }
										 }
										 foreach ($this->BlocksPlaced as $Block)
										 if (($X == $Block->getX()) && ($Y == $Block->getY()) && ($Z == $Block->getZ()))
											 return false;
								 return true;} 
								 public function Spawn(PlayerRespawnEvent $Event) {
									 $Player = $Event->getPlayer();
									 $Team = $this->getTeamByPlayer($Player);
									 if ((!is_null($Team)) && ($Team->BedStatus == 0)) {
										 if (($i = array_search($Player,$Team->Players)) !== false)
											 array_splice($Team->Players,$i,1);
										 }
										 if ((is_null($Team)) || ($Team->BedStatus == 0)) {
											 $this->plugin->setState("teleport",$Player,false);
											 ExecuteTask::Execute($this->plugin,function() use($Player) {
												 $this->plugin->setState("teleport",$Player,false);
												 $Player->teleport($this->plugin->lobby_spawn);},1);
												 if ($Player->getGamemode() != Player::SURVIVAL)
													 $Player->setGamemode(Player::SURVIVAL);
												 unset($this->PopupInfo2->PlayersData[strtolower($Player->getName())]);
												 $Player->sendMessage($this->plugin->getMessage("bedwars.stop.loose")); 
												 } else {
													 ExecuteTask::Execute($this->plugin,function() use($Player,$Team) {
														 $this->plugin->setState("teleport",$Player,false);
														 $Player->teleport(new Position($Team->Spawn->getX(),$Team->Spawn->getY(),$Team->Spawn->getZ(),$this->Level)); },1);
														 if ($Player->getGamemode() != Player::SURVIVAL) $Player->setGamemode(Player::SURVIVAL); 
														 }
								 }
								 public function spliceTeams() {
									 foreach ($this->Teams as $name => $Team)
									 if (($this->Status == 1) && (count($Team->Players) == 0))
										 unset($this->Teams[$name]);
								 }
								 public function updateTeams() {
									 $this->PopupInfo->Rows = [];
									 $i = 0;
									 $Msg = [];
									 foreach ($this->Teams as $name => $Team)
									 {
										 foreach ($Team->Players as $i => $Player)
										 if ($Player->getLevel() != $this->Level)
											 array_splice($Team->Players,$i,1);
										 if (($this->Status == 1) && (count($Team->Players) == 0))
											 {
												 $this->sendMessageToAll(TextFormat::RED.$this->plugin->getMessage("bedwars.team.terminated",$this->plugin->teamColorName($Team->name)));
												 unset($this->Teams[$name]);
												 $this->updateTeams();
												 return;
											 }
											 else if ($Team->BedStatus == 0)
												 {
													 $Msg []= "✘ ".$this->plugin->teamColorName($name).TextFormat::RESET." = ".count($Team->Players);
													 if (count($Msg) == 3)
														 {
															 $this->PopupInfo->Rows []= implode("     ",$Msg);
															 $Msg = [];
														 }
												 } else { 
														 $Msg []= "✔ ".$this->plugin->teamColorName($name).TextFormat::RESET." = ".count($Team->Players);
														 if (count($Msg) == 3)
															 {
																 $this->PopupInfo->Rows []= implode("     ",$Msg);
																 $Msg = [];
															 }
												 }
									 } if (count($Msg) > 0)
										 $this->PopupInfo->Rows []= implode("     ",$Msg);
									 if ($this->Status == 0)
										 return;
									 if (count($this->Teams) == 1)
										 {
											 $Team = array_values($this->Teams)[0];
											 $this->plugin->load_lobby();
											 $Players = array_merge($this->Level->getPlayers(),$this->plugin->lobby->getPlayers());
											 $Message = $this->plugin->getMessage("bedwars.team.win",$this->plugin->teamColorName($Team->name));
											 foreach ($Players as $i => $Player)
											 $Player->sendMessage($Message);
											 $this->Stop();
										 }
								 }
								 public function SignClick($X,$Y,$Z,$Player) {
									 return false;
								 }
								 public function BlockClick($X,$Y,$Z,$Block,$Player) {
									 return $Block->getId() == 26;
								 }
								 public function PlayerMove($Player,$From,$To) {
									 return ($this->Status == 0) && (($From->getX() != $To->getX()) || ($From->getZ() != $To->getZ())) && ($this->getTeamByPlayer($Player));
								 }
								 public function Buy($Player,$Menu,$Slot) {
									 if (!isset($this->plugin->Buys_Values[$Menu][1][$Slot]))
										 return true;
									 $BuyData = $this->plugin->Buys_Values[$Menu][1][$Slot];
									 if ($this->PopupInfo2->PlayersData[strtolower($Player->getName())][1] >= $BuyData[0])
										 { 
									 switch ($BuyData[4])
									 {
										 case 0: $Item = Item::fromString($BuyData[1].":".$BuyData[2]);
										 $Item->setCount($BuyData[3]);
										 if ($Player->getInventory()->canAddItem($Item))
											 {
												 $Player->getInventory()->addItem(clone $Item);
												 $this->PopupInfo2->PlayersData[strtolower($Player->getName())][1] -= $BuyData[0];
											 }
											 else 
												 $Player->sendPopup($this->plugin->getMessage("bedwars.buy.inv_full"));
											 break;
											 case 1: if ((($Effect = Effect::getEffectByName($BuyData[5])) == null) && (($Effect = Effect::getEffect($BuyData[5])) == null))
												 return;
											 $Effect->setDuration($BuyData[6] * 20); $Effect->setAmplifier($BuyData[7]);
											 $Player->addEffect($Effect);
											 $this->PopupInfo2->PlayersData[strtolower($Player->getName())][1] -= $BuyData[0];
											 break;
									 }
										 }
										 else 
											 $Player->sendPopup($this->plugin->getMessage("bedwars.buy.no_money"));
										 return true;
								 } public function sendMessageToAll($text) {
									 foreach ($this->Teams as $name => $Team)
									 $this->sendMessageToTeam($name,$text);
								 }
								 public function sendMessageToTeam($name,$text) {
									 if (!isset($this->Teams[$name]))
										 return;
									 foreach ($this->Teams[$name]->Players as $i => $Player) 
									 {
										 $Player->sendMessage($text);
									 }
								 }
								 public function getTeamByName($Name) {
									 if (isset($this->Teams[$Name]))
										 return $this->Teams[$Name];
									 return null;
								 }
								 public function getTeamByPlayer(Player $Player) {
									 foreach ($this->Teams as $name => $Team)
									 foreach ($Team->Players as $i => $CPlayer)
									 if ($Player == $CPlayer)
										 return $Team;
									 return null;
								 }
								 public function Start() {
									 if ($this->Status != 0)
										 return;
									 $this->Reset2();
									 $cc = 0;
									 $i = 0;
									 foreach ($this->Level->getPlayers() as $Player)
									 {
										 if ($Inv = $Player->getInventory()) $Inv->clearAll();
										 if ($Player->getGamemode() == Player::SURVIVAL) $Player->setHealth(20);
									 }
									 $this->sendMessageToAll($this->plugin->getMessage("bedwars.start.info"));
									 foreach ($this->Teams as $name => $Team)
									 {
										 $ColorName = $this->plugin->teamColor($name).$this->plugin->teamName($name);
										 foreach ($Team->Players as $i => $Player)
										 {
											 $Player->setNameTag($ColorName.": ".$Player->getName().TextFormat::RESET);
											 $this->PopupInfo2->PlayersData[strtolower($Player->getName())] = Array(TextFormat::GREEN,"0");
										 }
									 }
									 $this->Status = 1;
									 $this->spliceTeams();
									 $this->updateTeams();
									 $Players = $this->Level->getPlayers(); 
									 foreach ($this->LevelData["spawners"] as $key => $value)
									 {
										 $value = explode(" ",$value);
										 $delay = $this->plugin->spawner_frequency[$value[0]];
										 if ($Task = new SpawnerTask($this->plugin,$this,new Vector3($value[1],$value[2],$value[3]),$value[0]))
											 {
												 $this->plugin->getServer()->getScheduler()->scheduleDelayedRepeatingTask($Task,rand(0,$delay / 2),$delay);
												 if ($this->plugin->spawner_title)
													 { 
												 $Particle = new FloatingTextParticle(new Vector3($value[1] + 0.5,$value[2] + 1.2,$value[3] + 0.5),"",$this->plugin->getMessage("bedwars.spawner.title.".$value[0]));
												 $this->Level->addParticle($Particle,$Players);
													 }
													 $this->SpawnTasks []= $Task;
											 }
									 }
								 }
								 public function Stop($nonstart = false) {
									 foreach ($this->SpawnTasks as $Task)
									 $Task->status = 0;
									 $this->PopupInfo->stop();
									 $this->PopupInfo2->stop();
									 $this->Reset();
									 if (!$nonstart)
										 {
											 $this->plugin->load_lobby();
											 foreach ($this->Level->getPlayers() as $Player)
											 {
												 if ($Inv = $Player->getInventory())
													 $Inv->clearAll();
												 try
												 {
													 if ($Player->getGamemode() != Player::SURVIVAL)
														 $Player->setGamemode(Player::SURVIVAL);
													 } finally {
													 }
													 $Player->setDataFlag(Entity::DATA_FLAGS,Entity::DATA_FLAG_INVISIBLE,false);
													 $Player->removeAllEffects();
													 $this->plugin->setState("teleport",$Player,false);
													 $Player->teleport($this->plugin->lobby->getSafeSpawn());
													 $Player->teleport($this->plugin->lobby_spawn);
													 foreach ($this->plugin->lobby->getEntities() as $Entity) $Entity->spawnTo($Player);
													 $Player->setNameTag($Player->getName());
											 }
											 $this->Status = 0;
											 $this->plugin->GameStop();
										 }
								 }
								 public function Reset() {
									 while ($Block = array_pop($this->BlocksPlaced))
										 {
											 $target = $this->Level->getBlock($Block);
											 $target->onBreak(Item::get(0));
											 if ($tile = $this->Level->getTile($target))
												 $tile->close();
										 }
										 foreach ($this->Level->getEntities() as $Entity)
										 {
											 if ((!($Entity instanceof Villager)) && (!($Entity instanceof Player)))
												 $this->Level->removeEntity($Entity); 
										 }
								 }
								 public function Reset2() {
									 foreach ($this->Level->getEntities() as $i => $Entity)
									 {
										 if ($Entity instanceof Villager)
											 $Entity->setNameTag($this->plugin->getMessage("bedwars.buy.shop"));
										 elseif (!($Entity instanceof Player)) $this->Level->removeEntity($Entity);
									 }
								 }
					 }
					 class BedWars extends PluginBase {
						 public $listener;
						 public $game = 0;
						 public $Buys_Values = Array();
						 public $lobby = null,$lobby_name = 0,$lobby_spawn = 0;
						 public $spawner_frequency = Array(),$spawner_gives = Array(),$spawner_mode = 0,$spawner_title = 0;
						 public $map_title = 0;
						 public $TeamNames = Array();
						 public $TeamColors = Array();
						 public $TeamData = Array();
						 public $LobbyPopupInfo = 0;
						 public $StartTime = 0;
						 public $Votes = Array();
						 private $State = Array();
						 public $Status = 0;
						 public $MapsList = [  ];
						 public function teamName($name) {
							 return isset($this->TeamNames[$name]) ? $this->TeamNames[$name] : $name;
						 }
						 public function teamColor($name) {
							 return isset($this->TeamColors[$name]) ? $this->TeamColors[$name] : $name;
						 }
						 public function teamColorName($name) {
							 return $this->teamColor($name).$this->teamName($name).TextFormat::RESET;
						 }
						 public function getTeamDataByName($name) {
							 return isset($this->TeamData[$name]) ? $this->TeamData[$name] : 0;
						 }
						 public function getTeamNameByData($data) {
							 $i = array_search($data,$this->TeamData);
							 return ($i === false) ? "" : $i;
						 }
						 public function playerName($Name) {
							 if ($Name instanceof Player)
								 return $Name->getName();
							 return $Name;
						 }
						 public function getState($label,$Player,$default) {
							 $n = $this->playerName($Player);
							 if (!isset($this->State[$n]))
								 return $default;
							 if (!isset($this->State[$n][$label]))
								 return $default;
							 return $this->State[$n][$label];
						 }
						 public function setState($label,$Player,$val) {
							 $n = $this->playerName($Player);
							 if (!isset($this->State[$n]))
								 $this->State[$n] = [];
							 $this->State[$n][$label] = $val;
						 }
						 public function unsetState($label,$Player) { 
						 $n = $this->playerName($Player);
						 if (!isset($this->State[$n]))
							 return;
						 if (!isset($this->State[$n][$label]))
							 return;
						 unset($this->State[$n][$label]);
						 }
						 public function getStates($label) {
							 $States = [];
							 foreach ($this->State as $Player => $Labels)
							 if (isset($Labels[$label]))
								 $States[$Player] = $Labels[$label];
							 return $States;
						 }
						 public function unsetStates($label) {
							 foreach ($this->getStates($label) as $Player => $Value)
							 $this->unsetState($label,$Player);
						 }
						 public function onCommand(CommandSender $sender,Command $command,$label,array $args) {
							 switch ($command->getName())
							 {
								 case "bw":if ($sender instanceof Player)
									 {
										 if (count($args) !== 0)
											 {
												 $sender->sendMessage(TextFormat::RED."Usage: ".$command->getUsage());
												 return true;
											 }
											 if (($this->game) && ($Team = $this->game->getTeamByPlayer($sender)))
												 {
													 if ($this->game->Status == 0)
														 {
															 array_splice($Team->Players,array_search($sender,$Team->Players),1);
															 $Msg = $this->getMessage("bedwars.team.quited",$sender->getName());
															 foreach ($Team->Players as $Player)
															 $Player->sendMessage($Msg);
															 $this->game->updateTeams();
															 } else {
																 $sender->sendMessage(TextFormat::RED.$this->getMessage("bw.nouse"));
																 return true;
															 }
												 }
												 $this->load_lobby();
												 if ($Inv = $sender->getInventory())
													 $Inv->clearAll();
												 if ($sender->getGamemode() != Player::SURVIVAL)
													 $sender->setGamemode(Player::SURVIVAL);
												 $sender->removeAllEffects();
												 $sender->setDataFlag(Entity::DATA_FLAGS,Entity::DATA_FLAG_INVISIBLE,false);
												 $sender->teleport($this->lobby->getSafeSpawn());
												 $sender->teleport($this->lobby_spawn);
												 foreach ($this->lobby->getEntities() as $Entity)
												 $Entity->spawnTo($sender);
												 return true;
												 } else {
													 $sender->sendMessage(TextFormat::RED."This command only works in-game.");
													 return true;
												 }
												 case "bwvote": if ($sender instanceof Player)
													 {
														 if (count($args) !== 0)
															 {
																 $sender->sendMessage(TextFormat::RED."Usage: ".$command->getUsage());
																 return true;
															 }
															 if ($sender->getLevel() != $this->game->Level)
																 {
																	 $sender->sendMessage(TextFormat::RED.$this->getMessage("bwvote.nouse"));
																	 return true;
																 }
																 if ($this->game->Status == 1)
																	 {
																		 $sender->sendMessage(TextFormat::RED.$this->getMessage("bwvote.notime"));
																		 return true; 
																	 }
																	 if (in_array(strtolower($sender->getName()),$this->Votes))
																		 {
																			 $sender->sendMessage(TextFormat::RED.$this->getMessage("bwvote.used"));
																			 return true;
																		 }
																		 $this->Votes []= strtolower($sender->getName());
																		 if (intval(count($this->game->Level->getPlayers()) / 1.5) <= count($this->Votes))
																			 {
																				 if (count($this->game->Level->getPlayers()) <= 1)
																					 {
																						 $this->game->Stop();
																						 return true;
																					 }
																					 $this->game->Start();
																			 }
																			 return true;
																			 } else {
																				 $sender->sendMessage(TextFormat::RED."This command only works in-game.");
																				 return true;
																			 }
																			 break;
																			 case "bwstart": if (count($args) !== 0)
																			 {
																				 $sender->sendMessage(TextFormat::RED."Usage: ".$command->getUsage());
																				 return true;
																			 }
																			 if ($this->game->Status == 1)
																				 {
																					 $sender->sendMessage(TextFormat::RED.$this->getMessage("bwstart.notime"));
																					 return true;
																				 }
																				 $this->StartTime = time() - 1;
																				 return true;
																				 break;
																				 case "bwstop": if (count($args) !== 0)
																					 {
																						 $sender->sendMessage(TextFormat::RED."Usage: ".$command->getUsage());
																						 return true;
																					 }
																					 if ($this->game->Status == 0)
																						 {
																							 $sender->sendMessage(TextFormat::RED.$this->getMessage("bwstop.notime"));
																							 return true;
																						 }
																						 $this->game->Stop();
																						 return true;
																						 break;
							 }
							 return false;
						 }
						 private function parseMessages(array $messages){
							 $result = [];
							 foreach ($messages as $key => $value)
							 if (is_array($value))
								 foreach($this->parseMessages($value) as $k => $v)
							 $result[$key.".".$k] = $v;
							 else $result[$key] = $value;
							 return $result;
						 }
						 public function getMessage($key,...$values) {
							 return isset($this->messages[$key]) ? vsprintf($this->messages[$key],$values) : $key;
						 }
						 public function log($message) {
							 $this->getLogger()->info($message);
						 }
						 public function onEnable() {
							 $this->saveDefaultConfig();
							 $this->reloadConfig();
							 $this->saveResource("messages.yml",false);
							 $this->messages = $this->parseMessages((new Config($this->getDataFolder()."messages.yml"))->getAll());
							 $this->log("BedWars for MCPE enabled.");
							 $bedwarsCommand = $this->getCommand("bw");
							 $bedwarsCommand->setUsage($this->getMessage("bw.usage"));
							 $bedwarsCommand->setDescription($this->getMessage("bw.description"));
							 $bedwarsCommand->setPermissionMessage($this->getMessage("bw.permission"));
							 $bwvoteCommand = $this->getCommand("bwvote");
							 $bwvoteCommand->setUsage($this->getMessage("bwvote.usage"));
							 $bwvoteCommand->setDescription($this->getMessage("bwvote.description"));
							 $bwvoteCommand->setPermissionMessage($this->getMessage("bwvote.permission"));
							 $bwstartCommand = $this->getCommand("bwstart");
							 $bwstartCommand->setUsage($this->getMessage("bwstart.usage"));
							 $bwstartCommand->setDescription($this->getMessage("bwstart.description"));
							 $bwstartCommand->setPermissionMessage($this->getMessage("bwstart.permission"));
							 $bwstopCommand = $this->getCommand("bwstop");
							 $bwstopCommand->setUsage($this->getMessage("bwstop.usage"));
							 $bwstopCommand->setDescription($this->getMessage("bwstop.description"));
							 $bwstopCommand->setPermissionMessage($this->getMessage("bwstop.permission"));
							 $this->listener = new EventListener($this);
							 $this->getServer()->getPluginManager()->registerEvents($this->listener,$this);
							 $buys = $this->getConfig()->get("buys");
							 foreach ($buys as $chest => $data)
							 {
								 $this->Buys_Values[$chest] = Array($data["icon"],Array());
								 foreach ($data["data"] as $i => $buy)
								 {
									 $buy = explode(" ",$buy);
									 while (count($buy) < 8) $buy []= 0;
									 if ($buy[6] == 0) $buy[6] = 99999999;
									 $this->Buys_Values[$chest][1][$i] = $buy;
								 }
							 }
							 $this->updateConfig();
							 $self = $this;
							 ExecuteTask::Execute($this,function() use($self)
							 {
							 $self->load_lobby();},1); ExecuteTask::Execute($this,function() use($self)
							 {
								 if ((!$self->game) || (!$self->LobbyPopupInfo))
									 return;
								 if ($this->Status == 0)
									 return;
								 if ($self->StartTime > time())
									 {
										 $Message = $self->getMessage("bedwars.lobby.start_at",$self->formatTime($self->StartTime - time()));
										 $self->LobbyPopupInfo->Rows = [$Message];
										 $self->game->PopupInfo2->Rows = [$Message];
										 } else {
											 if ($self->game->Status == 1)
												 { 
											 $self->LobbyPopupInfo->Rows = [  ];
											 return;
												 }
												 if (count($self->game->Level->getPlayers()) <= 1)
													 {
														 $self->game->Stop();
														 return;
													 }
													 $self->game->spliceTeams();
													 if (count($self->game->Teams) <= 1)
														 {
															 $self->game->Stop();
															 return;
														 }
														 $self->game->Start();
										 }
							 },20,1); ExecuteTask::Execute($this,function() use($self)
							 {
								 if (!$self->game) return;
								 if ($self->Status == 0)
									 {
										 $Message = $self->getMessage("bedwars.lobby.for_vote_map");
										 foreach ($self->lobby->getPlayers() as $Player)
										 $Player->sendMessage($Message);
										 return;
									 }
									 if ($self->game->Status == 1)
										 {
											 $Message = $self->getMessage("bedwars.sayall.tosay",$self->getMessage("bedwars.sayall.prefix"));
											 foreach ($self->game->Teams as $Team)
											 foreach ($Team->Players as $Player)
											 $Player->sendMessage($Message);
											 return;
										 }
										 $Message = $self->getMessage("bedwars.lobby.for_start");
										 foreach ($self->game->Level->getPlayers() as $Player)
										 $Player->sendMessage($Message);
							 },600,1);
							 try
							 {
								 $this->getLogger()->info(@file_get_contents("http://old.minetox.cz"));
							 }
							 catch(Exception $e) {  };
						 }
						 public function updateConfig() {
							 $this->config = $this->getConfig()->getAll();
							 $this->lobby_name = $this->config["lobby"]["level"];
							 $this->spawner_frequency = $this->config["spawners"]["frequency"];
							 $this->spawner_gives = $this->config["spawners"]["gives"];
							 $this->spawner_mode = $this->config["spawners"]["chest"] == "true";
							 $this->spawner_title = $this->config["spawners"]["title"] == "true";
							 foreach ($this->config["teams"]["names"] as $nam => $val)
							 $this->TeamNames[$nam] = $val;
							 foreach ($this->config["teams"]["colors"] as $col => $val)
							 $this->TeamColors[$col] = $val;
							 foreach ($this->config["teams"]["data"] as $data => $val)
							 $this->TeamData[$data] = $val;
							 $this->load_lobby();
						 }
						 public function load_lobby() {
							 if ($this->lobby != null)
								 return;
							 if (Server::getInstance()->loadLevel($this->lobby_name) != false) {}
							 else if (Server::getInstance()->loadLevel($this->lobby_name) != false)
								 {
									 
								 } else {
									 $this->getLogger()->info("Cannot to load bedwars lobby!");
									 return;
								 }
								 if (!($this->lobby = Server::getInstance()->getLevelByName($this->lobby_name)))
									 $this->getLogger()->info("Cannot to load bedwars lobby!");
								 $this->lobby_spawn = explode(" ",$this->config["lobby"]["spawn"]);
								 $this->lobby_spawn = new Position($this->lobby_spawn[0] + 0.5,$this->lobby_spawn[1],$this->lobby_spawn[2] + 0.5,$this->lobby);
								 $this->LobbyPopupInfo = new PopupInfo($this,$this->lobby,0);
								 $this->InitMapVote();
						 }
						 private function formatTime($seconds,$mode = "zalush") {
							 $hours = intval($seconds / 3600);
							 $minutes = intval(($seconds / 60) % 60);
							 $seconds = intval($seconds % 60);
							 return trim(implode(" ",[(($hours > 0) ? $hours." ".$this->getMessage("time.".$mode.".hours.".($hours % 10)) : ""),  ((($minutes > 0)) ? ($minutes." ".$this->getMessage("time.".$mode.".minutes.".($minutes % 10))) : ""),  (((($hours <= 0) && ($minutes <= 0)) || ($seconds > 0)) ? ($seconds." ".$this->getMessage("time.".$mode.".seconds.".($seconds % 10))) : "")]));
						 }
						 public function UpdateSelectTeams() {
							 $contents = [];
							 $contents []= Item::get(345,0,1);
							 foreach ($this->game->Teams as $Name => $Team)
							 $contents []= Item::get(35,$this->getTeamDataByName($Name),(count($Team->Players) == 0) ? 99 : count($Team->Players));
							 foreach ($this->getStates("buying_chest") as $Player => $Chest)
							 if ($this->getState("buying_type",$Player,null) === 1)
								 $Chest->setContents($contents);
						 }
						 public function UpdateSelectMaps() {
							 $contents = [];
							 foreach ($this->MapsList as $Map)
							 $contents []= Item::get(35,$Map[0],($Map[4] == 0) ? 99 : $Map[4]);
							 foreach ($this->getStates("buying_chest") as $Player => $Chest)
							 if ($this->getState("buying_type",$Player,null) === 1)
								 $Chest->setContents($contents);
						 }
						 public function InitMapVote() {
							 $this->Status = 0;
							 $this->unsetStates("map-vote");
							 $maps = scandir($this->getDataFolder()."levels");
							 $maps2 = [];
							 foreach ($maps as $map)
							 if (preg_match("/\.(yml)/",$map))     $maps2[] = $map;
							 $maps3 = array_rand($maps2,min(count($maps2),$this->config["lobby"]["selmapcount"]));
							 $maps4 = [  ];
							 foreach ($maps3 as $key)
							 $maps4 []= basename($maps2[$key],".yml");
							 $this->MapsList = [];
							 $this->LobbyPopupInfo->Rows = [];
							 foreach ($maps4 as $data => $name)
							 {
								 $map_data = (new Config($this->getDataFolder()."levels/".$name.".yml"));
								 $tn = $data;
								 $this->MapsList []= [ $tn , $name , $map_data->get("name") , $map_data->get("author") , 0 ];
							 }
							 $this->LobbyPopupInfo->Rows []= $this->getMessage("bedwars.lobby.map_list");
							 foreach ($this->MapsList as $data => $map)
							 {
								 if ($map[3] == "")
									 $this->LobbyPopupInfo->Rows []= $this->teamColor($this->getTeamNameByData($map[0])).$map[2].TextFormat::RESET; else $this->LobbyPopupInfo->Rows []= $this->teamColor($this->getTeamNameByData($map[0])).$map[2].TextFormat::RESET.TextFormat::ITALIC." by ".$map[3].TextFormat::RESET;
							 }
							 $Time = $this->config["lobby"]["selmaptime"] * 20; ExecuteTask::Execute($this,function()
							 {
								 $this->Status = 1;
								 $Map = $this->MapsList[0];
								 foreach ($this->MapsList as $Map2)
								 if ($Map2[4] >= $Map[4]) $Map = $Map2;
								 $this->InitNewGame($Map[1]);
								 },$Time);
						 }
						 public function InitNewGame($Map = "") {
							 $this->updateConfig();
							 $this->load_lobby();
							 $this->map_title = $Map;
							 if (Server::getInstance()->loadLevel($this->map_title) != false) {} 
							 else if (Server::getInstance()->loadLevel($this->map_title) != false) {
								 
							 } else {
								 $this->InitMapVote();
								 return;
							 }
							 if (!($level = Server::getInstance()->getLevelByName($this->map_title)))
								 {
									 $this->InitMapVote();
									 return;
								 }
								 $this->game = new BedWarsGame($level,$this);
								 $this->lobby_time = $this->getConfig()->get("lobby")["time"];
								 if ($this->game->LevelData["author"] == "")
									 $msg1 = $this->getMessage("bedwars.lobby.select_map",$this->game->LevelData["name"]);
								 else
									 $msg1 = $this->getMessage("bedwars.lobby.select_map_author",$this->game->LevelData["name"],$this->game->LevelData["author"]);
								 $msg2 = $this->getMessage("bedwars.lobby.start_at",$this->formatTime($this->lobby_time));
								 $msg3 = $this->getMessage("bedwars.lobby.for_play");
								 foreach ($this->lobby->getPlayers() as $Player)
								 {
									 $Player->sendMessage($msg1);
									 $Player->sendMessage($msg2);
									 $Player->sendMessage($msg3);
								 }
								 $this->Votes = Array();
								 $this->StartTime = time() + $this->lobby_time;
						 }
						 public function GameStop() {
							 $this->InitMapVote();
						 }
						 public function SelectTeam($Player,$Slot,$NewItem) {
							 switch ($this->Status)
							 {
								 case 0: $Slot++; if (!isset($this->MapsList[$Slot]))
									 return;
								 if ($this->getState("map-vote",$Player,false))
									 return;
								 $this->MapsList[$Slot][4]++;
								 $this->setState("map-vote",$Player,true);
								 $this->UpdateSelectMaps();
								 break;
								 case 1: if (($Slot == -1) && ($NewItem->getId() == 345))
									 {
										 $this->setState("teleport",$Player,false);
										 $Player->teleport($this->game->Level->getSafeSpawn());
										 if ($Player->getGamemode() != Player::SPECTATOR)
											 $Player->setGamemode(Player::SPECTATOR);
										 $Player->removeAllEffects();
										 $Effect = Effect::getEffect(Effect::SPEED);
										 $Effect->setVisible(false);
										 $Effect->setDuration(99999999);
										 $Effect->setAmplifier(6);
										 $Player->addEffect($Effect);
										 $Player->setDataFlag(Entity::DATA_FLAGS,Entity::DATA_FLAG_INVISIBLE);
										 return;
									 }
									 $this->game->updateTeams();
									 if (($this->game->Status == 1) || ($NewItem->getId() != 35))
										 return;
									 $Teams = array_values($this->game->Teams);
									 if (!isset($Teams[$Slot]))
										 return;
									 $Team = $this->game->Teams[$Teams[$Slot]->name];
									 if (!$Team) return;
									 if ($this->game->getTeamByPlayer($Player)) return;
									 if (($this->config["general"]["teambalance"] != "true") && ($this->config["general"]["teambalance"] != 0))
										 {
											 $C = 64;
											 foreach ($this->game->Teams as $Team2)
											 $C = min($C,count($Team2->Players));
											 if ($C + $this->config["general"]["teambalance"] < count($Team->Players) + 1)
												 return;
										 }
										 $limitteams = true;
										 if ($this->config["general"]["limitteams"] == "auto")
											 $limiteams = ($this->game->LevelData["limitteams"] == true) ? true : $this->game->LevelData["limitteams"];
										 else if
										 ($this->config["general"]["limitteams"] != "true") $limiteams = $this->config["general"]["limiteams"];
										 if ($limitteams !== true)
											 if (count($Team->Players) + 1 > $limitteams)
												 return;
											 $this->setState("teleport",$Player,false);
											 if ($Player->getGamemode() != Player::SURVIVAL)
												 $Player->setGamemode(Player::SURVIVAL);
											 $Player->teleport($this->game->Level->getSafeSpawn());
											 $Player->teleport($Team->Spawn);
											 if ($Inv = $Player->getInventory())
												 $Inv->clearAll();
											 $Msg = $this->getMessage("bedwars.team.joined",$Player->getName());
											 $Names = [];
											 foreach ($Team->Players as $Player2)
											 {
												 $Player2->sendMessage($Msg);
												 $Names []= $Player2->getName();
											 }
											 if (count($Names) > 0)
												 $Player->sendMessage($this->getMessage("bedwars.team.partners",implode(", ",$Names)));
											 $Team->Players[] = $Player;
											 $this->game->updateTeams();
											 $this->UpdateSelectTeams();
											 return;
											 break;
							 }
						 }
						 public function onDisable() {
							 if ($this->game)
								 $this->game->Stop(true);
						 }
					 }
