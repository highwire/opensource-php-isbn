<?php

/**
 * isbn 
 *
 * Copyright (c) 2010-2011 Board of Trustees, Leland Stanford Jr. University
 * This software is open-source licensed under the GNU Public License Version 2 or later
 */
class isbn {
    public  $isbn;
    private $isbnData;
    private $isbnLength;
    private $isValid;
    private $prefix;
    private $group;
    private $groupRecord;
    private $publisher;
    private $article;
    private $check;	
    private $isIsbn10;
    private $isIsbn13;
    private $rest;
    private $isbn10;
    private $isbn10Hyphenated;
    private $isbn13;
    private $isbn13Hyphenated;
    private $check10;
    private $check13;
    
  function __construct($isbn) {
    $ret = TRUE;
    $this->isbnData = $this->setIsbnData();
    $this->isbn = $isbn;
    $this->isbnLength = strlen($isbn);
    $this->parse();
    $this->process();
  }
  
  public function isValid() {
    return $this->isValid;
  }

  public function parse() {
    $matches = array();
    
    // 9 digits may or may not be followed by X
    if(preg_match('/^\d{9}[\dX]$/', $this->isbn)) {
      
      $this->isValid =  TRUE;		
      $this->isIsbn10 = TRUE;
      $this->isIsbn13 = false;
      $this->setGroupRecord();
      $this->setAllDetails();
    } 
    else if (preg_match('/^(\d+)-(\d+)-(\d+)-([\dX])$/' , $this->isbn, $matches)) {
      
      $this->isValid =  TRUE;		
      $this->isIsbn10 = TRUE;
      $this->isIsbn13 = false;
      $this->group = $matches[1];
      $this->publisher = $matches[2];
      $this->article = $matches[3];
      $this->check = $matches[4];
    }
    else if (preg_match('/^(978|979)(\d{9}[\dX]$)/', $this->isbn, $matches)) {
      
      $this->isValid =  TRUE;		
      $this->isIsbn10 = false;
      $this->isIsbn13 = true;
      $this->prefix = $matches[1]; 
      $this->setGroupRecord(substr($this->isbn, strlen($this->prefix)));
      $this->setAllDetails();
    }
    else if (preg_match('/^(978|979)-(\d+)-(\d+)-(\d+)-([\dX])$/', $this->isbn, $matches) && $this->isbnLength == 17) {
        
      $this->isValid =  TRUE;		
      $this->isIsbn10 = false;
      $this->isIsbn13 = true;
      $this->prefix = $matches[1]; 
      $this->group = $matches[2];
      $this->publisher = $matches[3];
      $this->article = $matches[4];
      $this->check = $matches[5];
    }
    else {
      $this->isValid =  FALSE;		
    }
  }
  
  private function setGroupRecord($isbn = NULL) { 
    $matches = array();  
    global $isbn_data;
    if(!$isbn) {
      $isbn = $this->isbn;    
    }  
    
    foreach ($isbn_data['groups'] as $key => $value) {
      if (preg_match('/^' . $key . '(.+)/' , $isbn, $matches)) {
        $this->group = $key;
        $this->groupRecord = $value;
        $this->rest = $matches[1];  
      }
    }
  }
  
  private function setAllDetails() {
        
    for ($i = 0; $i < count($this->groupRecord['ranges']); ++$i) {
      $key = substr($this->rest, 0, strlen($this->groupRecord['ranges'][$i][0]));    
      if ($key >= $this->groupRecord['ranges'][$i][0] && $key <= $this->groupRecord['ranges'][$i][1] ) {
        $this->publisher = $key;
        $rest = substr($this->rest, strlen($key));
        $this->article = substr($rest, 0, strlen($rest) -1 );
        $this->check = substr($rest, -1, 1);
      } 
    }    
  }
  
  public function process() {
    $prefix = $this->prefix ? $this->prefix : '978';
    
    $check10 = implode('', array($this->group, $this->publisher, $this->article));      
    $this->check10 = $this->calcCheckDigit($check10);
    
    $check13 = implode('' , array($prefix, $this->group, $this->publisher, $this->article));    
    $this->check13 = $this->calcCheckDigit($check13);
    
    
    
    $this->isbn13 = $check13 . $this->check13;
    $this->isbn13Hyphenated = implode('-', array($prefix, $this->group, $this->publisher, $this->article, $this->check13));
     
    

    if ($prefix == "978") {
      $this->isbn10 = $check10 . $this->check10;    
      $this->isbn10Hyphenated = implode('-', array($this->group, $this->publisher, $this->article, $this->check10));  
    }
    
  }
  
  public function calcCheckDigit($isbn) {
    
    if(preg_match('/^\d{9}[\dX]?$/', $isbn)) {
     $c = 0;
      for ($n = 0; $n < 9; ++$n) {
        
        $c += (10 - $n) * $isbn[$n];
        $n;
      }  
      $c = (11 - $c % 11) % 11;
      
      if ($c == 10) {
          $c = 'X'; 
      }
      
    }  
    else if (preg_match('/^(978|979)(\d{9}[\dX]?$)/', $isbn)) {      
      $c = 0;
      for ($n = 0; $n < 12; $n += 2) {
        $c += (int)$isbn[$n] + 3 * $isbn[$n + 1];
      }
      $c = (10 - $c % 10) % 10;
      
    }
  return $c;  
  }
  
  public function isIsbn10() {
    return $this->isIsbn10;    
  }
  
  public function isIsbn13() {
    return $this->isIsbn13;    
  }
  
  public function asIsbn13() {
    return $this->isbn13;    
  }
  
  public function asIsbn10() {
    return $this->isbn10;    
  }
  
  public function asIsbn13Hyphens() {
    return $this->isbn13Hyphenated;    
  }
  
  public function asIsbn10Hyphens() {
    return $this->isbn10Hyphenated;    
  }

private function setIsbnData() {
  $this->isbn_data = array();
  $this->isbn_data['groups_version'] = 20090129;
  $this->isbn_data['groups'] = array(
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
        "2" => array (
                "name" => "French speaking area",
                "ranges" => array (array("00","19"), array("200", "349"), array("35000", "39999"),  array("400", "699"), 
                             array("7000", "8399"), array("84000", "89999"), array("900000", "949999"), array("9500000", "9999999")
                                  )	
                      ),		
        "3" => array (
                "name" => "German speaking area",
                "ranges" => array (array("00", "02"), array("030", "033"), array("0340", "0369"),  array("03700", "03999"), 
                            array("04", "19"), array("200", "699"), array("7000", "8499"), array("85000", "89999"), 
                            array("900000", "949999"), array("9500000", "9539999"), array("95400", "96999"), array("9700000", "9899999"),
                            array("99000", "99499"), array("99500", "99999")            
                                  )	
                      ),	
        "4" => array (
          "name" => "Japan",
          "ranges" => array (array("00", "19"), array("200", "699"), array("7000", "8499"),  array("85000", "89999"), 
                      array("900000", "949999"), array("9500000", "9999999")      
                            )	
                      ),	
        "5" => array (
          "name" => "Russian Federation",
          "ranges" => array (array("00", "19"), array("200", "420"), array("4210", "4299"),  array("430", "430"), 
                      array("4310", "4399"), array("440", "440"), array("4410", "4499"), array("450", "699"), array("7000", "8499"), 
                      array("85000", "89999"), array("900000", "909999"), array("91000", "91999"),  array("9200", "9299"),
                      array("93000", "94999"), array("9500", "9799"), array("98000", "98999"), array("9900000", "9909999"), 
                      array("9910", "9999"))	
                      ),	
         
         "602" => array(
                    "name" => "Indonesia", 
                  "ranges" => array(array("00", "19"), array("200", "799"), array("8000", "9499"), 
                              array("95000", "99999")
          )
        ),
      "7" => array ( 
            "name" => "China, People's Republic",
            "ranges" => array (array(00,09),array(100,499),array(5000,7999),array(80000,89999),array(900000,999999))
          ),
"80" => array ( 
            "name" => "Czech Republic; Slovakia",
            "ranges" => array (array(00,19),array(200,699),array(7000,8499),array(85000,89999),array(900000,999999))
          ),
"81" => array ( 
            "name" => "India",
            "ranges" => array (array(00,19),array(200,699),array(7000,8499),array(85000,89999),array(900000,999999))
          ),
"82" => array ( 
            "name" => "Norway",
            "ranges" => array (array(00,19),array(200,699),array(7000,8999),array(90000,98999),array(990000,999999))
          ),
"83" => array ( 
            "name" => "Poland",
            "ranges" => array (array(00,19),array(200,599),array(60000,69999),array(7000,8499),array(85000,89999),array(900000,999999))
          ),
"84" => array ( 
            "name" => "Spain",
            "ranges" => array (array(00,19),array(200,699),array(7000,8499),array(85000,89999),array(9000,9199),array(920000,923999),array(92400,92999),array(930000,949999),array(95000,96999),array(9700,9999))
          ),
"85" => array ( 
            "name" => "Brazil",
            "ranges" => array (array(00,19),array(200,599),array(60000,69999),array(7000,8499),array(85000,89999),array(900000,979999),array(98000,99999))
          ),
"86" => array ( 
            "name" => "Serbia and Montenegro",
            "ranges" => array (array(00,29),array(300,599),array(6000,7999),array(80000,89999),array(900000,999999))
          ),
"87" => array ( 
            "name" => "Denmark",
            "ranges" => array (array(00,29),array(400,649),array(7000,7999),array(85000,94999),array(970000,999999))
          ),
"88" => array ( 
            "name" => "Italian speaking area",
            "ranges" => array (array(00,19),array(200,599),array(6000,8499),array(85000,89999),array(900000,949999),array(95000,99999))
          ),
"89" => array ( 
            "name" => "Korea",
            "ranges" => array (array(00,24),array(250,549),array(5500,8499),array(85000,94999),array(950000,999999))
          ),
"90" => array ( 
            "name" => "Netherlands, Belgium (Flemish)",
            "ranges" => array (array(00,19),array(200,499),array(5000,6999),array(70000,79999),array(800000,849999),array(8500,8999),array(900000,909999),array(940000,949999))
          ),
"91" => array ( 
            "name" => "Sweden",
            "ranges" => array (array(0,1),array(20,49),array(500,649),array(7000,7999),array(85000,94999),array(970000,999999))
          ),
"92" => array ( 
            "name" => "International Publishers (Unesco, EU), European Community Organizations",
            "ranges" => array (array(0,5),array(60,79),array(800,899),array(9000,9499),array(95000,98999),array(990000,999999))
          ),
"93" => array ( 
            "name" => "India",
            "ranges" => array (array(00,09),array(100,499),array(5000,7999),array(80000,94999),array(950000,999999))
          ),
"94" => array ( 
            "name" => "Netherlands",
            "ranges" => array (array(000,599),array(6000,8999),array(90000,99999))
          ),
"600" => array ( 
            "name" => "Iran",
            "ranges" => array (array(00,09),array(100,499),array(5000,8999),array(90000,99999))
          ),
"601" => array ( 
            "name" => "Kazakhstan",
            "ranges" => array (array(00,19),array(200,699),array(7000,7999),array(80000,84999),array(85,99))
          ),
"602" => array ( 
            "name" => "Indonesia",
            "ranges" => array (array(00,19),array(200,799),array(8000,9499),array(95000,99999))
          ),
"603" => array ( 
            "name" => "Saudi Arabia",
            "ranges" => array (array(00,04),array(500,799),array(8000,8999),array(90000,99999))
          ),
"604" => array ( 
            "name" => "Vietnam",
            "ranges" => array (array(0,4),array(50,89),array(900,979),array(9800,9999))
          ),
"605" => array ( 
            "name" => "Turkey",
            "ranges" => array (array(00,09),array(100,399),array(4000,5999),array(60000,89999))
          ),
"606" => array ( 
            "name" => "Romania",
            "ranges" => array (array(0,0),array(10,49),array(500,799),array(8000,9199),array(92000,99999))
          ),
"607" => array ( 
            "name" => "Mexico",
            "ranges" => array (array(00,39),array(400,749),array(7500,9499),array(95000,99999))
          ),
"608" => array ( 
            "name" => "Macedonia",
            "ranges" => array (array(0,0),array(10,19),array(200,449),array(4500,6499),array(65000,69999),array(7,9))
          ),
"609" => array ( 
            "name" => "Lithuania",
            "ranges" => array (array(00,39),array(400,799),array(8000,9499),array(95000,99999))
          ),
"950" => array ( 
            "name" => "Argentina",
            "ranges" => array (array(00,49),array(500,899),array(9000,9899),array(99000,99999))
          ),
"951" => array ( 
            "name" => "Finland",
            "ranges" => array (array(0,1),array(20,54),array(550,889),array(8900,9499),array(95000,99999))
          ),
"952" => array ( 
            "name" => "Finland",
            "ranges" => array (array(00,19),array(200,499),array(5000,5999),array(60,65),array(6600,6699),array(67000,69999),array(7000,7999),array(80,94),array(9500,9899),array(99000,99999))
          ),
"953" => array ( 
            "name" => "Croatia",
            "ranges" => array (array(0,0),array(10,14),array(150,549),array(55000,59999),array(6000,9499),array(95000,99999))
          ),
"954" => array ( 
            "name" => "Bulgaria",
            "ranges" => array (array(00,29),array(300,799),array(8000,8999),array(90000,92999),array(9300,9999))
          ),
"955" => array ( 
            "name" => "Sri Lanka",
            "ranges" => array (array(0000,0999),array(1000,1999),array(20,54),array(550,799),array(8000,9499),array(95000,99999))
          ),
"956" => array ( 
            "name" => "Chile",
            "ranges" => array (array(00,19),array(200,699),array(7000,9999))
          ),
"957" => array ( 
            "name" => "Taiwan, China",
            "ranges" => array (array(00,02),array(0300,0499),array(05,19),array(2000,2099),array(21,27),array(28000,30999),array(31,43),array(440,819),array(8200,9699),array(97000,99999))
          ),
"958" => array ( 
            "name" => "Colombia",
            "ranges" => array (array(00,56),array(57000,59999),array(600,799),array(8000,9499),array(95000,99999))
          ),
"959" => array ( 
            "name" => "Cuba",
            "ranges" => array (array(00,19),array(200,699),array(7000,8499))
          ),
"960" => array ( 
            "name" => "Greece",
            "ranges" => array (array(00,19),array(200,659),array(6600,6899),array(690,699),array(7000,8499),array(85000,99999))
          ),
"961" => array ( 
            "name" => "Slovenia",
            "ranges" => array (array(00,19),array(200,599),array(6000,8999),array(90000,94999))
          ),
"962" => array ( 
            "name" => "Hong Kong",
            "ranges" => array (array(00,19),array(200,699),array(7000,8499),array(85000,86999),array(8700,8999),array(900,999))
          ),
"963" => array ( 
            "name" => "Hungary",
            "ranges" => array (array(00,19),array(200,699),array(7000,8499),array(85000,89999),array(9000,9999))
          ),
"964" => array ( 
            "name" => "Iran",
            "ranges" => array (array(00,14),array(150,249),array(2500,2999),array(300,549),array(5500,8999),array(90000,96999),array(970,989),array(9900,9999))
          ),
"965" => array ( 
            "name" => "Israel",
            "ranges" => array (array(00,19),array(200,599),array(7000,7999),array(90000,99999))
          ),
"966" => array ( 
            "name" => "Ukraine",
            "ranges" => array (array(00,14),array(1500,1699),array(170,199),array(2000,2999),array(300,699),array(7000,8999),array(90000,99999))
          ),
"967" => array ( 
            "name" => "Malaysia",
            "ranges" => array (array(00,29),array(300,499),array(5000,5999),array(60,89),array(900,989),array(9900,9989),array(99900,99999))
          ),
"968" => array ( 
            "name" => "Mexico",
            "ranges" => array (array(01,39),array(400,499),array(5000,7999),array(800,899),array(9000,9999))
          ),
"969" => array ( 
            "name" => "Pakistan",
            "ranges" => array (array(0,1),array(20,39),array(400,799),array(8000,9999))
          ),
"970" => array ( 
            "name" => "Mexico",
            "ranges" => array (array(01,59),array(600,899),array(9000,9099),array(91000,96999),array(9700,9999))
          ),
"971" => array ( 
            "name" => "Philippines",
            "ranges" => array (array(000,019),array(02,02),array(0300,0599),array(06,09),array(10,49),array(500,849),array(8500,9099),array(91000,99999))
          ),
"972" => array ( 
            "name" => "Portugal",
            "ranges" => array (array(0,1),array(20,54),array(550,799),array(8000,9499),array(95000,99999))
          ),
"973" => array ( 
            "name" => "Romania",
            "ranges" => array (array(0,0),array(100,169),array(1700,1999),array(20,54),array(550,759),array(7600,8499),array(85000,88999),array(8900,9499),array(95000,99999))
          ),
"974" => array ( 
            "name" => "Thailand",
            "ranges" => array (array(00,19),array(200,699),array(7000,8499),array(85000,89999),array(90000,94999),array(9500,9999))
          ),
"975" => array ( 
            "name" => "Turkey",
            "ranges" => array (array(00000,00999),array(01,24),array(250,599),array(6000,9199),array(92000,98999),array(990,999))
          ),
"976" => array ( 
            "name" => "Caribbean Community",
            "ranges" => array (array(0,3),array(40,59),array(600,799),array(8000,9499),array(95000,99999))
          ),
"977" => array ( 
            "name" => "Egypr",
            "ranges" => array (array(00,19),array(200,499),array(5000,6999),array(700,999))
          ),
"978" => array ( 
            "name" => "Nigeria",
            "ranges" => array (array(000,199),array(2000,2999),array(30000,79999),array(8000,8999),array(900,999))
          ),
"979" => array ( 
            "name" => "Indonesia",
            "ranges" => array (array(000,099),array(1000,1499),array(15000,19999),array(20,29),array(3000,3999),array(400,799),array(8000,9499),array(95000,99999))
          ),
"980" => array ( 
            "name" => "Venezuela",
            "ranges" => array (array(00,19),array(200,599),array(6000,9999))
          ),
"981" => array ( 
            "name" => "Singapore",
            "ranges" => array (array(00,11),array(120,299),array(3000,9999))
          ),
"982" => array ( 
            "name" => "South Pacific",
            "ranges" => array (array(00,09),array(100,699),array(70,89),array(9000,9999))
          ),
"983" => array ( 
            "name" => "Malaysia",
            "ranges" => array (array(00,01),array(020,199),array(2000,3999),array(40000,44999),array(45,49),array(50,79),array(800,899),array(9000,9899),array(99000,99999))
          ),
"984" => array ( 
            "name" => "Bangladesh",
            "ranges" => array (array(00,39),array(400,799),array(8000,8999),array(90000,99999))
          ),
"985" => array ( 
            "name" => "Belarus",
            "ranges" => array (array(00,39),array(400,599),array(6000,8999),array(90000,99999))
          ),
"986" => array ( 
            "name" => "Taiwan, China",
            "ranges" => array (array(00,11),array(120,559),array(5600,7999),array(80000,99999))
          ),
"987" => array ( 
            "name" => "Argentina",
            "ranges" => array (array(00,09),array(1000,1999),array(20000,29999),array(30,49),array(500,899),array(9000,9499),array(95000,99999))
          ),
"988" => array ( 
            "name" => "Hongkong",
            "ranges" => array (array(00,16),array(17000,19999),array(200,799),array(8000,9699),array(97000,99999))
          ),
"989" => array ( 
            "name" => "Portugal",
            "ranges" => array (array(0,1),array(20,54),array(550,799),array(8000,9499),array(95000,99999))
          ),
"9933" => array ( 
            "name" => "Syria",
            "ranges" => array (array(0,0),array(10,39),array(400,899),array(9000,9999))
          ),
"9934" => array ( 
            "name" => "Latvia",
            "ranges" => array (array(0,0),array(10,49),array(500,799),array(8000,9999))
          ),
"9935" => array ( 
            "name" => "Iceland",
            "ranges" => array (array(0,0),array(10,39),array(400,899),array(9000,9999))
          ),
"9936" => array ( 
            "name" => "Afghanistan",
            "ranges" => array (array(0,1),array(20,39),array(400,799),array(8000,9999))
          ),
"9937" => array ( 
            "name" => "Nepal",
            "ranges" => array (array(0,2),array(30,49),array(500,799),array(8000,9999))
          ),
"9938" => array ( 
            "name" => "Tunisia",
            "ranges" => array (array(00,79),array(800,949),array(9500,9999))
          ),
"9939" => array ( 
            "name" => "Armenia",
            "ranges" => array (array(0,4),array(50,79),array(800,899),array(9000,9999))
          ),
"9940" => array ( 
            "name" => "Montenegro",
            "ranges" => array (array(0,1),array(20,49),array(500,899),array(9000,9999))
          ),
"9941" => array ( 
            "name" => "Georgia",
            "ranges" => array (array(0,0),array(10,39),array(400,899),array(9000,9999))
          ),
"9942" => array ( 
            "name" => "Ecuador",
            "ranges" => array (array(00,89),array(900,994),array(9950,9999))
          ),
"9943" => array ( 
            "name" => "Uzbekistan",
            "ranges" => array (array(00,29),array(300,399),array(4000,9999))
          ),
"9944" => array ( 
            "name" => "Turkey",
            "ranges" => array (array(0,2),array(300,499),array(5000,5999),array(60,89),array(900,999))
          ),
"9945" => array ( 
            "name" => "Dominican Republic",
            "ranges" => array (array(00,00),array(010,079),array(08,39),array(400,569),array(57,57),array(580,849),array(8500,9999))
          ),
"9946" => array ( 
            "name" => "Korea, P.D.R.",
            "ranges" => array (array(0,1),array(20,39),array(400,899),array(9000,9999))
          ),
"9947" => array ( 
            "name" => "Algeria",
            "ranges" => array (array(0,1),array(20,79),array(800,999))
          ),
"9948" => array ( 
            "name" => "United Arab Emirates",
            "ranges" => array (array(00,39),array(400,849),array(8500,9999))
          ),
"9949" => array ( 
            "name" => "Estonia",
            "ranges" => array (array(0,0),array(10,39),array(400,899),array(9000,9999))
          ),
"9950" => array ( 
            "name" => "Palestine",
            "ranges" => array (array(00,29),array(300,840),array(8500,9999))
          ),
"9951" => array ( 
            "name" => "Kosova",
            "ranges" => array (array(00,39),array(400,849),array(8500,9999))
          ),
"9952" => array ( 
            "name" => "Azerbaijan",
            "ranges" => array (array(0,1),array(20,39),array(400,799),array(8000,9999))
          ),
"9953" => array ( 
            "name" => "Lebanon",
            "ranges" => array (array(0,0),array(10,39),array(400,599),array(60,89),array(9000,9999))
          ),
"9954" => array ( 
            "name" => "Morocco",
            "ranges" => array (array(0,1),array(20,39),array(400,799),array(8000,9999))
          ),
"9955" => array ( 
            "name" => "Lithuania",
            "ranges" => array (array(00,39),array(400,929),array(9300,9999))
          ),
"9956" => array ( 
            "name" => "Cameroon",
            "ranges" => array (array(0,0),array(10,39),array(400,899),array(9000,9999))
          ),
"9957" => array ( 
            "name" => "Jordan",
            "ranges" => array (array(00,39),array(400,699),array(70,84),array(8500,9999))
          ),
"9958" => array ( 
            "name" => "Bosnia and Herzegovina",
            "ranges" => array (array(0,0),array(10,49),array(500,899),array(9000,9999))
          ),
"9959" => array ( 
            "name" => "Libya",
            "ranges" => array (array(0,1),array(20,79),array(800,949),array(9500,9999))
          ),
"9960" => array ( 
            "name" => "Saudi Arabia",
            "ranges" => array (array(00,59),array(600,899),array(9000,9999))
          ),
"9961" => array ( 
            "name" => "Algeria",
            "ranges" => array (array(0,2),array(30,69),array(700,949),array(9500,9999))
          ),
"9962" => array ( 
            "name" => "Panama",
            "ranges" => array (array(00,54),array(5500,5599),array(56,59),array(600,849),array(8500,9999))
          ),
"9963" => array ( 
            "name" => "Cyprus",
            "ranges" => array (array(0,2),array(30,54),array(550,749),array(7500,9999))
          ),
"9964" => array ( 
            "name" => "Ghana",
            "ranges" => array (array(0,6),array(70,94),array(950,999))
          ),
"9965" => array ( 
            "name" => "Kazakhstan",
            "ranges" => array (array(00,39),array(400,899),array(9000,9999))
          ),
"9966" => array ( 
            "name" => "Kenya",
            "ranges" => array (array(000,199),array(20,69),array(7000,7499),array(750,959),array(9600,9999))
          ),
"9967" => array ( 
            "name" => "Kyrgyzstan",
            "ranges" => array (array(00,39),array(400,899),array(9000,9999))
          ),
"9968" => array ( 
            "name" => "Costa Rica",
            "ranges" => array (array(00,49),array(500,939),array(9400,9999))
          ),
"9970" => array ( 
            "name" => "Uganda",
            "ranges" => array (array(00,39),array(400,899),array(9000,9999))
          ),
"9971" => array ( 
            "name" => "Singapore",
            "ranges" => array (array(0,5),array(60,89),array(900,989),array(9900,9999))
          ),
"9972" => array ( 
            "name" => "Peru",
            "ranges" => array (array(00,09),array(1),array(200,249),array(2500,2999),array(30,59),array(600,899),array(9000,9999))
          ),
"9973" => array ( 
            "name" => "Tunisia",
            "ranges" => array (array(00,05),array(060,089),array(0900,0999),array(10,69),array(700,969),array(9700,9999))
          ),
"9974" => array ( 
            "name" => "Uruguay",
            "ranges" => array (array(0,2),array(30,54),array(550,749),array(7500,9499),array(95,99))
          ),
"9975" => array ( 
            "name" => "Moldova",
            "ranges" => array (array(0,0),array(100,399),array(4000,4499),array(45,89),array(900,949),array(9500,9999))
          ),
"9976" => array ( 
            "name" => "Tanzania",
            "ranges" => array (array(0,5),array(60,89),array(900,989),array(9990,9999))
          ),
"9977" => array ( 
            "name" => "Costa Rica",
            "ranges" => array (array(00,89),array(900,989),array(9900,9999))
          ),
"9978" => array ( 
            "name" => "Ecuador",
            "ranges" => array (array(00,29),array(300,399),array(40,94),array(950,989),array(9900,9999))
          ),
"9979" => array ( 
            "name" => "Iceland",
            "ranges" => array (array(0,4),array(50,64),array(650,659),array(66,75),array(760,899),array(9000,9999))
          ),
"9980" => array ( 
            "name" => "Papua New Guinea",
            "ranges" => array (array(0,3),array(40,89),array(900,989),array(9900,9999))
          ),
"9981" => array ( 
            "name" => "Morocco",
            "ranges" => array (array(00,09),array(100,159),array(1600,1999),array(20,79),array(800,949),array(9500,9999))
          ),
"9982" => array ( 
            "name" => "Zambia",
            "ranges" => array (array(00,79),array(800,989),array(9900,9999))
          ),
"9983" => array ( 
            "name" => "Gambia",
            "ranges" => array (array(80,94),array(950,989),array(9900,9999))
          ),
"9984" => array ( 
            "name" => "Latvia",
            "ranges" => array (array(00,49),array(500,899),array(9000,9999))
          ),
"9985" => array ( 
            "name" => "Estonia",
            "ranges" => array (array(0,4),array(50,79),array(800,899),array(9000,9999))
          ),
"9986" => array ( 
            "name" => "Lithuania",
            "ranges" => array (array(00,39),array(400,899),array(9000,9399),array(940,969),array(97,99))
          ),
"9987" => array ( 
            "name" => "Tanzania",
            "ranges" => array (array(00,39),array(400,879),array(8800,9999))
          ),
"9988" => array ( 
            "name" => "Ghana",
            "ranges" => array (array(0,2),array(30,54),array(550,749),array(7500,9999))
          ),
"9989" => array ( 
            "name" => "Macedonia",
            "ranges" => array (array(0,0),array(100,199),array(2000,2999),array(30,59),array(600,949),array(9500,9999))
          ),
"99901" => array ( 
            "name" => "Bahrain",
            "ranges" => array (array(00,49),array(500,799),array(80,99))
          ),
"99902" => array ( 
            "name" => "Gabon - no ranges fixed yet",
            "ranges" => array ()
          ),
"99903" => array ( 
            "name" => "Mauritius",
            "ranges" => array (array(0,1),array(20,89),array(900,999))
          ),
"99904" => array ( 
            "name" => "Netherlands Antilles; Aruba, Neth. Ant",
            "ranges" => array (array(0,5),array(60,89),array(900,999))
          ),
"99905" => array ( 
            "name" => "Bolivia",
            "ranges" => array (array(0,3),array(40,79),array(800,999))
          ),
"99906" => array ( 
            "name" => "Kuwait",
            "ranges" => array (array(0,2),array(30,59),array(600,699),array(70,89),array(9,9))
          ),
"99908" => array ( 
            "name" => "Malawi",
            "ranges" => array (array(0,0),array(10,89),array(900,999))
          ),
"99909" => array ( 
            "name" => "Malta",
            "ranges" => array (array(0,3),array(40,94),array(950,999))
          ),
"99910" => array ( 
            "name" => "Sierra Leone",
            "ranges" => array (array(0,2),array(30,89),array(900,999))
          ),
"99911" => array ( 
            "name" => "Lesotho",
            "ranges" => array (array(00,59),array(600,999))
          ),
"99912" => array ( 
            "name" => "Botswana",
            "ranges" => array (array(0,3),array(400,599),array(60,89),array(900,999))
          ),
"99913" => array ( 
            "name" => "Andorra",
            "ranges" => array (array(0,2),array(30,35),array(600,604))
          ),
"99914" => array ( 
            "name" => "Suriname",
            "ranges" => array (array(0,4),array(50,89),array(900,949))
          ),
"99915" => array ( 
            "name" => "Maldives",
            "ranges" => array (array(0,4),array(50,79),array(800,999))
          ),
"99916" => array ( 
            "name" => "Namibia",
            "ranges" => array (array(0,2),array(30,69),array(700,999))
          ),
"99917" => array ( 
            "name" => "Brunei Darussalam",
            "ranges" => array (array(0,2),array(30,89),array(900,999))
          ),
"99918" => array ( 
            "name" => "Faroe Islands",
            "ranges" => array (array(0,3),array(40,79),array(800,999))
          ),
"99919" => array ( 
            "name" => "Benin",
            "ranges" => array (array(0,2),array(300,399),array(40,69),array(900,999))
          ),
"99920" => array ( 
            "name" => "Andorra",
            "ranges" => array (array(0,4),array(50,89),array(900,999))
          ),
"99921" => array ( 
            "name" => "Qatar",
            "ranges" => array (array(0,1),array(20,69),array(700,799),array(8,8),array(90,99))
          ),
"99922" => array ( 
            "name" => "Guatemala",
            "ranges" => array (array(0,3),array(40,69),array(700,999))
          ),
"99923" => array ( 
            "name" => "El Salvador",
            "ranges" => array (array(0,1),array(20,79),array(800,999))
          ),
"99924" => array ( 
            "name" => "Nicaragua",
            "ranges" => array (array(0,1),array(20,79),array(800,999))
          ),
"99925" => array ( 
            "name" => "Paraguay",
            "ranges" => array (array(0,3),array(40,79),array(800,999))
          ),
"99926" => array ( 
            "name" => "Honduras",
            "ranges" => array (array(0,0),array(10,59),array(600,999))
          ),
"99927" => array ( 
            "name" => "Albania",
            "ranges" => array (array(0,2),array(30,59),array(600,999))
          ),
"99928" => array ( 
            "name" => "Georgia",
            "ranges" => array (array(0,0),array(10,79),array(800,999))
          ),
"99929" => array ( 
            "name" => "Mongolia",
            "ranges" => array (array(0,4),array(50,79),array(800,999))
          ),
"99930" => array ( 
            "name" => "Armenia",
            "ranges" => array (array(0,4),array(50,79),array(800,999))
          ),
"99931" => array ( 
            "name" => "Seychelles",
            "ranges" => array (array(0,4),array(50,79),array(800,999))
          ),
"99932" => array ( 
            "name" => "Malta",
            "ranges" => array (array(0,0),array(10,59),array(600,699),array(7,7),array(80,99))
          ),
"99933" => array ( 
            "name" => "Nepal",
            "ranges" => array (array(0,2),array(30,59),array(600,999))
          ),
"99934" => array ( 
            "name" => "Dominican Republic",
            "ranges" => array (array(0,1),array(20,79),array(800,999))
          ),
"99935" => array ( 
            "name" => "Haiti",
            "ranges" => array (array(0,2),array(7,8),array(30,59),array(600,699),array(90,99))
          ),
"99936" => array ( 
            "name" => "Bhutan",
            "ranges" => array (array(0,0),array(10,59),array(600,999))
          ),
"99937" => array ( 
            "name" => "Macau",
            "ranges" => array (array(0,1),array(20,59),array(600,999))
          ),
"99938" => array ( 
            "name" => "Srpska",
            "ranges" => array (array(0,1),array(20,59),array(600,899),array(90,99))
          ),
"99939" => array ( 
            "name" => "Guatemala",
            "ranges" => array (array(0,5),array(60,89),array(900,999))
          ),
"99940" => array ( 
            "name" => "Georgia",
            "ranges" => array (array(0,0),array(10,69),array(700,999))
          ),
"99941" => array ( 
            "name" => "Armenia",
            "ranges" => array (array(0,2),array(30,79),array(800,999))
          ),
"99942" => array ( 
            "name" => "Sudan",
            "ranges" => array (array(0,4),array(50,79),array(800,999))
          ),
"99943" => array ( 
            "name" => "Alsbania",
            "ranges" => array (array(0,2),array(30,59),array(600,999))
          ),
"99944" => array ( 
            "name" => "Ethiopia",
            "ranges" => array (array(0,4),array(50,79),array(800,999))
          ),
"99945" => array ( 
            "name" => "Namibia",
            "ranges" => array (array(0,5),array(60,89),array(900,999))
          ),
"99946" => array ( 
            "name" => "Nepal",
            "ranges" => array (array(0,2),array(30,59),array(600,999))
          ),
"99947" => array ( 
            "name" => "Tajikistan",
            "ranges" => array (array(0,2),array(30,69),array(700,999))
          ),
"99948" => array ( 
            "name" => "Eritrea",
            "ranges" => array (array(0,4),array(50,79),array(800,999))
          ),
"99949" => array ( 
            "name" => "Mauritius",
            "ranges" => array (array(0,1),array(20,89),array(900,999))
          ),
"99950" => array ( 
            "name" => "Cambodia",
            "ranges" => array (array(0,4),array(50,79),array(800,999))
          ),
"99951" => array ( 
            "name" => "Congo - no ranges fixed yet",
            "ranges" => array ()
          ),
"99952" => array ( 
            "name" => "Mali",
            "ranges" => array (array(0,4),array(50,79),array(800,999))
          ),
"99953" => array ( 
            "name" => "Paraguay",
            "ranges" => array (array(0,2),array(30,79),array(800,999))
          ),
"99954" => array ( 
            "name" => "Bolivia",
            "ranges" => array (array(0,2),array(30,69),array(700,999))
          ),
"99955" => array ( 
            "name" => "Srpska",
            "ranges" => array (array(0,1),array(20,59),array(600,899),array(90,99))
          ),
"99956" => array ( 
            "name" => "Albania",
            "ranges" => array (array(00,59),array(600,999))
          ),
"99957" => array ( 
            "name" => "Malta",
            "ranges" => array (array(0,1),array(20,79),array(800,999))
          ),
"99958" => array ( 
            "name" => "Bahrain",
            "ranges" => array (array(0,4),array(50,94),array(950,999))
          ),
"99959" => array ( 
            "name" => "Luxembourg",
            "ranges" => array (array(0,2),array(30,59),array(600,999))
          ),
"99960" => array ( 
            "name" => "Malawi",
            "ranges" => array (array(0,0),array(10,94),array(950,999))
          ),
"99961" => array ( 
            "name" => "El Salvador",
            "ranges" => array (array(0,3),array(40,89),array(900,999))
          ),
"99962" => array ( 
            "name" => "Mongolia",
            "ranges" => array (array(0,4),array(50,79),array(800,999))
          )
    );  
    
  }
}
?>
