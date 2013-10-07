<?php

global $isbn_data;
$isbn_data = array();

$isbn_data['groups_version'] = 20090129;
$isbn_data['groups'] = array(
        "0" => array (
                "name" => "English speaking area",
                "ranges" => array (
                            array("00","19"), array("200", "699"), array("7000", "8499"), array("85000", "89999"), array("900000", "949999"), array("9500000", "9999999"))									
                      ),		

        "1" => array (
                "name" => "English speaking area",
                "ranges" => array (array("00","09"), array("100", "399"), array("4000", "5499"), 
                            array("55000", "86979"), array("869800", "998999")
                                  )	
                      ),		
         "602" => array(
                  "name" => "Indonesia", 
                  "ranges" => array(array("00", "19"), array("200", "799"), array("8000", "9499"), 
                              array("95000", "99999")
          )
        )
);