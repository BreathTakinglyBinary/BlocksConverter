<?php

declare(strict_types=1);

namespace matcracker\BlocksConverter;

use matcracker\BlocksConverter\commands\Convert;
use matcracker\BlocksConverter\commands\ConvertQueue;
use matcracker\BlocksConverter\commands\ToolBlock;
use matcracker\BlocksConverter\tasks\ToolBlockTask;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\plugin\PluginBase;

final class Loader extends PluginBase implements Listener
{

	public function onLoad(): void
	{
		@mkdir($this->getDataFolder() . "/backups", 0777, true);
		BlocksMap::load();
	}

	public function onEnable(): void
	{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getCommandMap()->register('blocksconverter', new Convert($this));
		$this->getServer()->getCommandMap()->register('blocksconverter', new ConvertQueue($this));
		$this->getServer()->getCommandMap()->register('blocksconverter', new ToolBlock());

		$this->getScheduler()->scheduleRepeatingTask(new ToolBlockTask(), 5);
	}

	public function onPlayerQuit(PlayerQuitEvent $event): void
	{
		ToolBlock::removePlayer($event->getPlayer());
	}

	public function onDisable(): void
	{
		$this->getScheduler()->cancelAllTasks();
	}
}
