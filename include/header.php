<?php
      
        // we won't be able to use our Database Model if Capstone\Database is not initialized
        $databaseBoot = new Capstone\Database;
        // this needs to be done on top of every page that needs authentication
        session_start();