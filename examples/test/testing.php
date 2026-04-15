<?php

require_once "../../st_pathdef.inc.php";
require_once "test_db_account.php";

STCheck::global_testfile_variables("_test_db_host");
STCheck::global_testfile_variables("_test_db_user");
STCheck::global_testfile_variables("_test_db_password");
STCheck::global_testfile_variables("_test_db_name");

//STCheck::debug("test.file", "04a_basic_main.php");
STCheck::debug("test.file", "01_first_try.php");
STCheck::debug("test.file.last", "01_test_allTables.php");
