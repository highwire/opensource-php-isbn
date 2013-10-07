<?php

// isbn.inc contains the area, publisher codes etc
require_once('isbn.inc');

/**
 * isbn 
 *
 * Copyright (c) 2010-2011 Board of Trustees, Leland Stanford Jr. University
 * This software is open-source licensed under the GNU Public License Version 2 or later
 */
class isbn {
    public $isbn;
    private $isbn_length;
    private $is_valid;
    private $prefix;
    private $group;
    private $group_record;
    private $publisher;
    private $article;
    private $check;	
    private $is_isbn10;
    private $is_isbn13;
    private $rest;
    private $as_isbn10;
    private $as_isbn10_h;
    private $as_isbn13;
    private $as_isbn13_h;
    private $check10;
    private $check13;
    
  function __construct($isbn) {
    $ret = TRUE;
    $this->isbn = $isbn;
    $this->isbn_length = strlen($isbn);
    $this->parse();
    $this->process();
  }
  
  public function get_is_valid() {
    return $this->is_valid;
  }

  public function parse() {
    $matches = array();
    
    // 9 digits may or may not be followed by X
    if(preg_match('/^\d{9}[\dX]$/', $this->isbn)) {
      
      $this->is_valid =  TRUE;		
      $this->is_isbn10 = TRUE;
      $this->is_isbn13 = false;
      $this->get_group_record();
      $this->get_all_details();
    } 
    else if (preg_match('/^(\d+)-(\d+)-(\d+)-([\dX])$/' , $this->isbn, $matches)) {
      
      $this->is_valid =  TRUE;		
      $this->is_isbn10 = TRUE;
      $this->is_isbn13 = false;
      $this->group = $matches[1];
      $this->publisher = $matches[2];
      $this->article = $matches[3];
      $this->check = $matches[4];
    }
    else if (preg_match('/^(978|979)(\d{9}[\dX]$)/', $this->isbn, $matches)) {
      
      $this->is_valid =  TRUE;		
      $this->is_isbn10 = false;
      $this->is_isbn13 = true;
      $this->prefix = $matches[1]; 
      $this->get_group_record(substr($this->isbn, strlen($this->prefix)));
      $this->get_all_details();
    }
    else if (preg_match('/^(978|979)-(\d+)-(\d+)-(\d+)-([\dX])$/', $this->isbn, $matches) && $this->isbn_length == 17) {
        
      $this->is_valid =  TRUE;		
      $this->is_isbn10 = false;
      $this->is_isbn13 = true;
      $this->prefix = $matches[1]; 
      $this->group = $matches[2];
      $this->publisher = $matches[3];
      $this->article = $matches[4];
      $this->check = $matches[5];
    }
    else {
      $this->is_valid =  FALSE;		
    }
  }
  
  public function get_group_record($isbn = NULL) { 
    $matches = array();  
    global $isbn_data;
    if(!$isbn) {
      $isbn = $this->isbn;    
    }  
    
    foreach ($isbn_data['groups'] as $key => $value) {
      if (preg_match('/^' . $key . '(.+)/' , $isbn, $matches)) {
        $this->group = $key;
        $this->group_record = $value;
        $this->rest = $matches[1];  
      }
    }
  }
  
  public function get_all_details() {
        
    for ($i = 0; $i < count($this->group_record['ranges']); ++$i) {
      $key = substr($this->rest, 0, strlen($this->group_record['ranges'][$i][0]));    
      if ($key >= $this->group_record['ranges'][$i][0] && $key <= $this->group_record['ranges'][$i][1] ) {
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
    $this->check10 = $this->calc_check_digit($check10);
    
    $check13 = implode('' , array($prefix, $this->group, $this->publisher, $this->article));    
    $this->check13 = $this->calc_check_digit($check13);
    
    
    
    $this->as_isbn13 = $check13 . ' ' . $this->check13;
    $this->as_isbn13_h = implode('-', array($prefix, $this->group, $this->publisher, $this->article, $this->check13));
     
    

    if ($prefix == "978") {
      $this->as_isbn10 = $check10 . ' ' . $this->check10;    
      $this->as_isbn10_h = implode('-', array($this->group, $this->publisher, $this->article, $this->check10));  
    }
    
  }
  
  public function calc_check_digit($isbn) {
    
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
  
  public function get_is_isbn10() {
    return $this->is_isbn10;    
  }
  
  public function get_is_isbn13() {
    return $this->is_isbn13;    
  }
  
  public function get_as_isbn13() {
    return $this->as_isbn13;    
  }
  
  public function get_as_isbn10() {
    return $this->as_isbn10;    
  }
  
  public function get_as_isbn13_h() {
    return $this->as_isbn13_h;    
  }
  
  public function get_as_isbn10_h() {
    return $this->as_isbn10_h;    
  }
}

?>
