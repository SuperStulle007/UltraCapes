<?php

/**
 * Copyright (c) 2019-2020 SuperStulle007
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

declare(strict_types=1);

namespace SuperStulle007\UltraCapes;

use pocketmine\entity\Skin;
use pocketmine\event\Listener;
use pocketmine\event\player\{PlayerChangeSkinEvent, PlayerJoinEvent};
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use SuperStulle007\UltraCapes\libs\jojoe77777\FormAPI\SimpleForm;
use SuperStulle007\UltraCapes\Commands\CapesCommand;

class Main extends PluginBase implements Listener {

    protected $skin = [];
    
    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveDefaultConfig();
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $this->playercape = new Config($this->getDataFolder() . "data.yml", Config::YAML);
        $this->getServer()->getCommandMap()->register("cape", $this->command[] = new CapesCommand($this));
        if(is_array($this->config->get("standard_capes"))) {
            foreach($this->config->get("standard_capes") as $cape){
            $this->saveResource("$cape.png");
        }
        $this->config->set("standard_capes", "done");
        $this->config->save();
    }
    }

    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $this->skin[$player->getName()] = $player->getSkin();
        if(file_exists($this->getDataFolder() . $this->playercape->get($player->getName()) . ".png")){
            $oldSkin = $player->getSkin();
            $capeData = $this->createCape($this->playercape->get($player->getName()));
            $setCape = new Skin($oldSkin->getSkinId(), $oldSkin->getSkinData(), $capeData, $oldSkin->getGeometryName(), $oldSkin->getGeometryData());
            $player->setSkin($setCape);
            $player->sendSkin();
        }else{
            $this->playercape->remove($player->getName());
            $this->playercape->save();
    }
    }

    public function createCape($capeName){
        
        $path = $this->getDataFolder() . "{$capeName}.png";
        $img = @imagecreatefrompng($path);
        $bytes = '';
        $l = (int)@getimagesize($path)[1];
        for ($y = 0; $y < $l; $y++) {
            for ($x = 0; $x < 64; $x++) {
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

    public function onChangeSkin(PlayerChangeSkinEvent $event){
        
        $player = $event->getPlayer();
        $this->skin[$player->getName()] = $player->getSkin();
    }

    public function openCapesUI($player) {
        $form = new SimpleForm(function (Player $player, $data = null) {
            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
            case 0:
            break;
            case 1:
            $oldSkin = $player->getSkin();
            $setCape = new Skin($oldSkin->getSkinId(), $oldSkin->getSkinData(), "", $oldSkin->getGeometryName(), $oldSkin->getGeometryData());
            $player->setSkin($setCape);
            $player->sendSkin();
            if($this->playercape->get($player->getName()) !== null){
               $this->playercape->remove($player->getName());
               $this->playercape->save();
            }
               $player->sendMessage($this->config->get("skin-resetted"));
            break;
            case 2:
            $this->openCapeListUI($player);
            break;
            }
         });
            $form->setTitle($this->config->get("UI-Title"));
            $form->setContent($this->config->get("UI-Content"));
            $form->addButton("§4Close");
            $form->addButton("§cRemove your capes", 0, "textures/ui/trash");
            $form->addButton("§aSelect a capes", 0, "textures/ui/dressing_room_capes");
            $form->sendToPlayer($player);
            }
                        
    public function openCapeListUI($player){
        $form = new SimpleForm(function (Player $player, $data = null) {
            $result = $data;
            if ($result === null) {
                return true;
            }
            $cape = $data;
            if(!file_exists($this->getDataFolder() . $data . ".png")) {
                $player->sendMessage("The choosen Skin is not available!");
            }else{
                if (!$player->hasPermission("$cape.cape")) {
                     $player->sendMessage($this->config->get("no-permissions"));
           } else {
            $oldSkin = $player->getSkin();
            $capeData = $this->createCape($cape);
            $setCape = new Skin($oldSkin->getSkinId(), $oldSkin->getSkinData(), $capeData, $oldSkin->getGeometryName(), $oldSkin->getGeometryData());
            $player->setSkin($setCape);
            $player->sendSkin();
            $msg = $this->config->get("cape-on");
            $msg = str_replace("{name}", $cape, $msg);
            $player->sendMessage($msg);
            $this->playercape->set($player->getName(), $cape);
            $this->playercape->save();
            }
        }
    });
        $form->setTitle($this->config->get("UI-Title"));
        $form->setContent($this->config->get("UI-Content"));
        foreach($this->getCapes() as $capes){
        $form->addButton("$capes", -1, "", $capes);
        }
        $form->sendToPlayer($player);
    }
                        
    public function getCapes(){
    $list = array();
     foreach(array_diff(scandir($this->getDataFolder()), ["..", "."]) as $data){
             $dat = explode(".", $data);
             if($dat[1] == "png"){
                array_push($list, $dat[0]);
                }
            }
    return $list;
    }
}
