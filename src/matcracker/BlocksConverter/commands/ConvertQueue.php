<?php

declare(strict_types=1);

namespace matcracker\BlocksConverter\commands;

use matcracker\BlocksConverter\Loader;
use matcracker\BlocksConverter\world\WorldManager;
use matcracker\BlocksConverter\world\WorldQueue;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\level\Level;
use pocketmine\utils\TextFormat;

final class ConvertQueue extends Command
{
	private $loader;

	public function __construct(Loader $loader)
	{
		parent::__construct(
			'convertqueue',
			'Allows to add in queue worlds for the conversion.',
			'/convertqueue <add|remove|status> <world_name|all>',
			['cq']
		);
		$this->loader = $loader;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args): bool
	{
		if (!$sender->hasPermission("blocksconverter.command.convertqueue")) {
			$sender->sendMessage(TextFormat::RED . "You don't have permission to run this command!");
			return false;
		}

		if (count($args) < 1 || count($args) > 2) {
			$sender->sendMessage($this->getUsage());
			return false;
		}

		$action = strtolower($args[0]);

		if ($action === "status") {
			if (!WorldQueue::isEmpty()) {
				$sender->sendMessage(TextFormat::GOLD . "Worlds in queue:");
				$queued = WorldQueue::getQueue();
				foreach ($queued as $queue) {
					$sender->sendMessage(TextFormat::AQUA . "- " . $queue->getWorld()->getName());
				}
			} else {
				$sender->sendMessage(TextFormat::RED . "The queue is empty!");
			}
			return true;
		}

		$worldName = $args[1] ?? null;
		/**@var string[] $worldNames */
		$worldNames = [];

		if (strtolower($worldName) === "all") {
			$worldNames = array_map(static function (Level $world): string {
				return $world->getName();
			}, $this->loader->getServer()->getLevels());
		} else {
			$worldNames[] = $worldName;
		}

		foreach ($worldNames as $worldName) {
			if ($action === "add") {
				if (!WorldQueue::isInQueue($worldName)) {
					if ($this->loader->getServer()->loadLevel($worldName)) {
						$world = $this->loader->getServer()->getLevelByName($worldName);
						if ($world !== null) {
							WorldQueue::addInQueue(new WorldManager($this->loader, $world));
							$sender->sendMessage(TextFormat::GREEN . "World \"{$worldName}\" has been added in queue.");
							continue;
						}
					}
					$sender->sendMessage(TextFormat::RED . "World \"{$worldName}\" isn't loaded or does not exist.");
				} else {
					$sender->sendMessage(TextFormat::RED . "World \"{$worldName}\" is already in queue!");
				}
			} elseif ($action === "remove") {
				if (WorldQueue::isInQueue($worldName)) {
					WorldQueue::removeFromQueue($worldName);
					$sender->sendMessage(TextFormat::GREEN . "World \"{$worldName}\" has been removed from the queue.");
				} else {
					$sender->sendMessage(TextFormat::GREEN . "World \"{$worldName}\" is not in queue.");
				}
			}
		}
		return true;
	}
}