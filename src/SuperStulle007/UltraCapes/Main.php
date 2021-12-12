<?php

/**
 * Copyright (c) 2019-2020 SuperStulle007
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

declare(strict_types=1);

namespace SuperStulle007\UltraCapes;

use pocketmine\command\{Command, CommandSender};
use pocketmine\entity\Skin;
use pocketmine\event\Listener;
use pocketmine\event\player\{PlayerChangeSkinEvent, PlayerJoinEvent};
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use SuperStulle007\UltraCapes\libs\jojoe77777\FormAPI\SimpleForm;

class Main extends PluginBase implements Listener {

    protected $skin = [];
    
    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveResource("config.yml");

        $capes = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        
        if(is_array($capes->get("standard_capes"))) {
            foreach($capes->get("standard_capes") as $cape){
                $this->saveResource("$cape.png");
            }

            $capes->set("standard_capes", "done");
            $capes->save();
        }
    }

    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $this->skin[$player->getName()] = $player->getSkin();
        $playercape = new Config($this->getDataFolder() . "data.yml", Config::YAML);
        
        if(file_exists($this->getDataFolder() . $playercape->get($player->getName()) . ".png")) {
            $oldSkin = $player->getSkin();
            $capeData = $this->createCape($playercape->get($player->getName()));
            $setCape = new Skin($oldSkin->getSkinId(), $oldSkin->getSkinData(), $capeData, $oldSkin->getGeometryName(), $oldSkin->getGeometryData());

            $player->setSkin($setCape);
            $player->sendSkin();
        } else {
            $playercape->remove($player->getName());
            $playercape->save();
        }
    }

    public function createCape($capeName) {
        $path = $this->getDataFolder() . "{$capeName}.png";
        $img = @imagecreatefrompng($path);
        $bytes = '';
        $l = (int) @getimagesize($path)[1];

        for($y = 0; $y < $l; $y++) {
            for($x = 0; $x < 64; $x++) {
                $rgba = @imagecolorat($img, $x, $y);
                $a = ((~((int)($rgba >> 24))) << 1) & 0xff;
                $r = ($rgba >> 16) & 0xff;
                $g = ($rgba >> 8) & 0xff;
                $b = $rgba & 0xff;
                $bytes .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }

        @imagedestroy($img);

        return $bytes;
    }

    public function onChangeSkin(PlayerChangeSkinEvent $event) {
        $player = $event->getPlayer();

        $this->skin[$player->getName()] = $player->getSkin();
    }

    public function onCommand(CommandSender $player, Command $command, string $label, array $args): bool {
        $cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $noperms = $cfg->get("no-permissions");
        $ingame = $cfg->get("ingame");

        if($command->getName() == "cape") {
            if(!$player instanceof Player) {
                $player->sendMessage($ingame);
            } else {
                if(!$player->hasPermission("cape.cmd")) {
                    $player->sendMessage($noperms);
                } else {
                    $this->openCapesUI($player);
                }
            }
        }
        
        return true;
    }
                            
    public function openCapesUI($player) {
        $cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $form = new SimpleForm(function(Player $player, $data = null) {
            $result = $data;

            if(is_null($result)) {
                return true;
            }

            switch($result) {
                case 0:
                    break;
                case 1:
                    $pdata = new Config($this->getDataFolder() . "data.yml", Config::YAML);
                    $cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
                    $oldSkin = $player->getSkin();
                    $setCape = new Skin($oldSkin->getSkinId(), $oldSkin->getSkinData(), "", $oldSkin->getGeometryName(), $oldSkin->getGeometryData());
                    
                    $player->setSkin($setCape);
                    $player->sendSkin();

                    if($pdata->get($player->getName()) !== null){
                        $pdata->remove($player->getName());
                        $pdata->save();
                    }
                    
                    $player->sendMessage($cfg->get("skin-resetted"));
                    break;
                case 2:
                    $this->openCapeListUI($player);
                    break;
            }
        });

        $form->setTitle($cfg->get("UI-Title"));
        $form->setContent($cfg->get("UI-Content"));
        $form->addButton("§4Abort", 0);
        $form->addButton("§0Remove your Cape", 1);
        $form->addButton("§eChoose a Cape", 2);
        $form->sendToPlayer($player);
    }
                        
    public function openCapeListUI($player) {
        $cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $form = new SimpleForm(function(Player $player, $data = null) {
            $result = $data;

            if(is_null($result)) {
                return true;
            }

            $cape = $data;
            $cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
            $pdata = new Config($this->getDataFolder() . "data.yml", Config::YAML);
            $noperms = $cfg->get("no-permissions");
            
            if(!file_exists($this->getDataFolder() . $data . ".png")) {
                $player->sendMessage("The choosen Skin is not available!");
            } else {
                if(!$player->hasPermission("$cape.cape")) {
                    $player->sendMessage($noperms);
                } else {
                    $oldSkin = $player->getSkin();
                    $capeData = $this->createCape($cape);
                    $setCape = new Skin($oldSkin->getSkinId(), $oldSkin->getSkinData(), $capeData, $oldSkin->getGeometryName(), $oldSkin->getGeometryData());

                    $player->setSkin($setCape);
                    $player->sendSkin();

                    $msg = $cfg->get("cape-on");
                    $msg = str_replace("{name}", $cape, $msg);

                    $player->sendMessage($msg);
                    $pdata->set($player->getName(), $cape);
                    $pdata->save();
                }
            }
        });

        $form->setTitle($cfg->get("UI-Title"));
        $form->setContent($cfg->get("UI-Content"));
        
        foreach($this->getCapes() as $capes) {
            $form->addButton("$capes", -1, "", $capes);
        }
        
        $form->sendToPlayer($player);
    }
                        
    public function getCapes() {
        $list = array();

        foreach(array_diff(scandir($this->getDataFolder()), ["..", "."]) as $data) {
            $dat = explode(".", $data);

            if($dat[1] == "png") {
                array_push($list, $dat[0]);
            }
        }
        
        return $list;
    }
}
