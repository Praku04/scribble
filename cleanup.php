<?php
   require_once 'includes/config.php';
   require_once 'includes/database.php';
   
   $db = Database::getInstance();
   $db->cleanupInactiveRooms();
   $db->cleanupInactivePlayers();
   $db->cleanupOldUpdates();
   
   echo "Cleanup completed\n";