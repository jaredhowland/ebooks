<?php
/**
  * Process and parse the temp_counter tables into individual tables
  *
  * @author Jared Howland <book.usage@jaredhowland.com>
  * @version 2013-05-14
  * @since 2013-05-04
  *
  */
  
class process {
  /***********************************/
  /* `book` table methods            */
  /***********************************/
  /**
    * Update the books table if necessary; return book_id from existing book otherwise
    *
    * @access protected
    * @param NULL
    * @return int book_id
    *
    */
  protected function update_books() {
    $book_id = $this->find_book_id();
    if(is_null($book_id)) {
      $book_id = $this->create_book();
    }
    return $book_id;
  }
  
  /**
    * Find the book_id if it already exists in the database; NULL if not there yet
    *
    * @access protected
    * @param NULL
    * @return mixed book_id int if exists in database; NULL otherwise
    *
    */
  protected function find_book_id() {
    // Search by DOI, ISBN, and ISSN
    $book_id = $this->search_by_standard_number();
    // If no match, enhance data and search by OCLC number
    if(is_null($book_id) && $this->result['isbn']) {
      $book_id = $this->search_by_oclc_number();
    }
    // If still no match, search by exact title
    elseif(is_null($book_id)) {
      $book_id = $this->search_by_title();
    }
    return $book_id;
  }
  
  /**
    * Search by standard numbers to see if book is already in database
    *
    * @access protected
    * @param NULL
    * @return mixed book_id int if it exists; NULL otherwise
    *
    */
  protected function search_by_standard_number() {
    // Define variables
    $doi  = $this->result['doi'];
    $isbn = $this->result['isbn'];
    $issn = $this->result['issn'];
    // Normalize NULL data to search database
    if(is_null($doi))  { $doi  = 'N/A'; }
    if(is_null($isbn)) { $isbn = 'N/A'; }
    if(is_null($issn)) { $issn = 'N/A'; }
    // Connect to database
    $database = new db;
    $db    = $database->connect();
    $sql   = 'SELECT id, doi, isbn, issn FROM books WHERE doi = :doi OR isbn = :isbn OR issn = :issn LIMIT 1';
    $query = $db->prepare($sql);
    $query->bindParam(':doi', $doi);
    $query->bindParam(':isbn', $isbn);
    $query->bindParam(':issn', $issn);
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_ASSOC);
    $db = NULL;
    // Return id if it exists in the database
    if(count($results) > 0) {
      return $results[0]['id'];
    } else {
      return NULL;
    }
  }
  
  /**
    * Search by OCLC number to see if book already exists in the database
    *
    * @access protected
    * @param NULL
    * @return mixed book_id int if it exists; NULL otherwise
    *
    */
  protected function search_by_oclc_number() {
    // Only enhance data if we cannot find the book by DOI, ISBN, or ISSN
    $this->enhance_data();
    // Define variables
    $oclc_num = $this->result['oclc_num'];
    // Normalize NULL data to search database
    if(is_null($oclc_num)) { $oclc_num = 'N/A'; }
    // Connect to database
    $database = new db;
    $db    = $database->connect();
    $sql   = 'SELECT id, oclc FROM books WHERE oclc = :oclc_num LIMIT 1';
    $query = $db->prepare($sql);
    $query->bindParam(':oclc_num', $oclc_num);
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_ASSOC);
    $db = NULL;
    // Return id if it exists in the database
    if(count($results) > 0) {
      return $results[0]['id'];
    } else {
      return NULL;
    }
  }
  
  /**
    * Search by exact title to see if book already exists in the database
    *
    * @access protected
    * @param NULL
    * @return mixed book_id int if it exists; NULL otherwise
    *
    */
  protected function search_by_title() {
    // Define variables
    $title      = $this->result['title'];
    $oclc_title = $this->result['oclc_title'];
    // Normalize NULL data to search database
    if(is_null($title))      { $title = 'N/A'; }
    if(is_null($oclc_title)) { $oclc_title = 'N/A'; }
    // Connect to database
    $database = new db;
    $db    = $database->connect();
    $sql   = 'SELECT id, title FROM books WHERE title = :title OR title = :oclc_title LIMIT 1';
    $query = $db->prepare($sql);
    $query->bindParam(':title', $title);
    $query->bindParam(':oclc_title', $oclc_title);
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_ASSOC);
    $db = NULL;
    // Return id if it exists in the database
    if(count($results) > 0) {
      return $results[0]['id'];
    } else {
      return NULL;
    }
  }
  
  /**
    * Create the book in the `books` table if it is not already there
    *
    * @access protected
    * @param NULL
    * @return int book_id
    *
    */
  protected function create_book() {
    $title         = $this->result['title'];
    $author        = $this->result['oclc_author'];
    $publisher     = $this->result['publisher'];
    $doi           = $this->result['doi'];
    $oclc_num      = $this->result['oclc_num'];
    $isbn          = $this->result['isbn'];
    $issn          = $this->result['issn'];
    $call_num      = $this->result['oclc_call_num'];
    $response_code = $this->result['oclc_response_code'];
    $fund_id       = $this->result['fund_id'];
    $oclc_title    = $this->result['oclc_title'];
    if($oclc_title) { $title = $oclc_title; }
    // Connect to database
    $database = new db;
    $db    = $database->connect();
    $sql   = 'INSERT INTO books (title, author, publisher, doi, oclc, isbn, issn, call_num, response_code, fund_id) VALUES (:title, :author, :publisher, :doi, :oclc, :isbn, :issn, :call_num, :response_code, :fund_id)';
    $query = $db->prepare($sql);
    $query->bindParam(':title', $title);
    $query->bindParam(':author', $author);
    $query->bindParam(':publisher', $publisher);
    $query->bindParam(':doi', $doi);
    $query->bindParam(':oclc', $oclc_num);
    $query->bindParam(':isbn', $isbn);
    $query->bindParam(':issn', $issn);
    $query->bindParam(':call_num', $call_num);
    $query->bindParam(':response_code', $response_code);
    $query->bindParam(':fund_id', $fund_id);
    $query->execute();
    $book_id = $db->lastInsertId();
    $db = NULL;
    return $book_id;
  }
  
  /***********************************/
  /* `vendor` table methods          */
  /***********************************/
  /**
    * Update the `vendors` table if necessary; Create vendor otherwise
    *
    * @access protected
    * @param NULL
    * @return int vendor_id of existing or newly created vendor
    *
    */
  protected function update_vendors() {
    $vendor_id = $this->search_by_vendor();
    if(is_null($vendor_id)) {
      $vendor_id = $this->create_vendor();
    }
    return $vendor_id;
  }
  
  /**
    * Search by vendor name to see if already exists in database
    *
    * @access protected
    * @param NULL
    * @return mixed vendor_id int if exists; NULL otherwise
    *
    */
  protected function search_by_vendor() {
    // Define variables
    $vendor = $this->result['vendor'];
    // Normalize NULL data to search database
    if(is_null($vendor)) { $vendor = 'N/A'; }
    // Connect to database
    $database = new db;
    $db    = $database->connect();
    $sql   = 'SELECT id, vendor FROM vendors WHERE vendor = :vendor LIMIT 1';
    $query = $db->prepare($sql);
    $query->bindParam(':vendor', $vendor);
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_ASSOC);
    $db = NULL;
    // Return id if it exists in the database
    if(count($results) > 0) {
      return $results[0]['id'];
    } else {
      return NULL;
    }
  }
  
  /**
    * Create vendor if it is not already in the `vendors` table
    *
    * @access protected
    * @param NULL
    * @return int vendor_id
    *
    */
  protected function create_vendor() {
    $vendor = $this->result['vendor'];
    // Connect to database
    $database = new db;
    $db    = $database->connect();
    $sql   = 'INSERT INTO vendors (vendor) VALUES (:vendor)';
    $query = $db->prepare($sql);
    $query->bindParam(':vendor', $vendor);
    $query->execute();
    $vendor_id = $db->lastInsertId();
    $db = NULL;
    return $vendor_id;
  }
  
  /**
    * Update the `books_vendors` table
    *
    * @access protected
    * @param int book_id
    * @param int vendor_id
    * @return int book_vendor_id
    *
    */
  protected function update_books_vendors($book_id, $vendor_id) {
    $book_vendor_id = $this->search_by_book_vendor_id($book_id, $vendor_id);
    if(is_null($book_vendor_id)) {
      $book_vendor_id = $this->create_book_vendor($book_id, $vendor_id);
    }
    return $book_vendor_id;
  }
  
  /**
    * Search for existing book_vendor_id
    *
    * @access protected
    * @param int book_id
    * @param int vendor_id
    * @return mixed book_vendor_id if exists; NULL otherwise
    *
    */
  protected function search_by_book_vendor_id($book_id, $vendor_id) {
    // Connect to database
    $database = new db;
    $db    = $database->connect();
    $sql   = 'SELECT id, vendor_id, book_id FROM books_vendors WHERE vendor_id = :vendor_id AND book_id = :book_id LIMIT 1';
    $query = $db->prepare($sql);
    $query->bindParam(':vendor_id', $vendor_id);
    $query->bindParam(':book_id', $book_id);
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_ASSOC);
    $db = NULL;
    // Return id if it exists in the database
    if(count($results) > 0) {
      return $results[0]['id'];
    } else {
      return NULL;
    }
  }
  
  /**
    * Create book_vendor if not already there
    *
    * @access protected
    * @param int book_id
    * @param int vendor_id
    * @return int book_vendor_id
    *
    */
  protected function create_book_vendor($book_id, $vendor_id) {
    // Connect to database
    $database = new db;
    $db    = $database->connect();
    $sql   = 'INSERT INTO books_vendors (book_id, vendor_id) VALUES (:book_id, :vendor_id)';
    $query = $db->prepare($sql);
    $query->bindParam(':book_id', $book_id);
    $query->bindParam(':vendor_id', $vendor_id);
    $query->execute();
    $book_vendor_id = $db->lastInsertId();
    $db = NULL;
    return $book_vendor_id;
  }
  
  /***********************************/
  /* `platforms` table methods       */
  /***********************************/
  /**
    * Update the `platforms` table if necessary
    *
    * @access protected
    * @param int vendor_id
    * @return int platform_id
    *
    */
  protected function update_platforms($vendor_id) {
    $platform_id = $this->search_by_platform($vendor_id);
    if(is_null($platform_id)) {
      $platform_id = $this->create_platform($vendor_id);
    }
    return $platform_id;
  }
  
  /**
    * Search by platform name
    *
    * @access protected
    * @param int vendor_id
    * @return mixed platform_id int if exists; NULL otherwise
    *
    */
  protected function search_by_platform($vendor_id) {
    // Define variables
    $platform = $this->result['platform'];
    // Normalize NULL data to search database
    if(is_null($platform)) { $platform = 'N/A'; }
    // Connect to database
    $database = new db;
    $db    = $database->connect();
    $sql   = 'SELECT id, vendor_id, platform FROM platforms WHERE vendor_id = :vendor_id AND platform = :platform LIMIT 1';
    $query = $db->prepare($sql);
    $query->bindParam(':vendor_id', $vendor_id);
    $query->bindParam(':platform', $platform);
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_ASSOC);
    $db = NULL;
    // Return id if it exists in the database
    if(count($results) > 0) {
      return $results[0]['id'];
    } else {
      return NULL;
    }
  }
  
  /**
    * Create the platform if it does not already exist in the database
    *
    * @access protected
    * @param int vendor_id
    * @return int platform_id
    *
    */
  protected function create_platform($vendor_id) {
    $platform = $this->result['platform'];
    // Connect to database
    $database = new db;
    $db    = $database->connect();
    $sql   = 'INSERT INTO platforms (vendor_id, platform) VALUES (:vendor_id, :platform)';
    $query = $db->prepare($sql);
    $query->bindParam(':vendor_id', $vendor_id);
    $query->bindParam(':platform', $platform);
    $query->execute();
    $platform_id = $db->lastInsertId();
    $db = NULL;
    return $platform_id;
  }
  
  /**
    * Update `books_platforms` table
    *
    * @access protected
    * @param int book_id
    * @param int platform_id
    * @return int book_platform_id
    *
    */
  protected function update_books_platforms($book_id, $platform_id) {
    $book_platform_id = $this->search_by_book_platform_id($book_id, $platform_id);
    if(is_null($book_platform_id)) {
      $book_platform_id = $this->create_book_platform($book_id, $platform_id);
    }
    return $book_platform_id;
  }
  
  /**
    * Search for existing book_platform_id
    *
    * @access protected
    * @param int book_id
    * @param int platform_id
    * @return mixed book_platform_id if exists; NULL otherwise
    *
    */
  protected function search_by_book_platform_id($book_id, $platform_id) {
    // Connect to database
    $database = new db;
    $db    = $database->connect();
    $sql   = 'SELECT id, platform_id, book_id FROM books_platforms WHERE platform_id = :platform_id AND book_id = :book_id LIMIT 1';
    $query = $db->prepare($sql);
    $query->bindParam(':platform_id', $platform_id);
    $query->bindParam(':book_id', $book_id);
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_ASSOC);
    $db = NULL;
    // Return id if it exists in the database
    if(count($results) > 0) {
      return $results[0]['id'];
    } else {
      return NULL;
    }
  }
  
  /**
    * Create books_platforms entry if not already in database
    *
    * @access protected
    * @param int book_id
    * @param int platform_id
    * @return int platform_id
    *
    */
  protected function create_book_platform($book_id, $platform_id) {
    // Connect to database
    $database = new db;
    $db    = $database->connect();
    $sql   = 'INSERT INTO books_platforms (book_id, platform_id) VALUES (:book_id, :platform_id)';
    $query = $db->prepare($sql);
    $query->bindParam(':book_id', $book_id);
    $query->bindParam(':platform_id', $platform_id);
    $query->execute();
    $book_vendor_id = $db->lastInsertId();
    $db = NULL;
    return $book_platform_id;
  }
  
  /**
    * Returns fund_id for given call number
    *
    * @access protected
    * @param string Call number
    * @return int|NULL fund_id if can be determined; NULL otherwise
    *
    */
  protected function get_fund_id($call_num) {
    $call_num_ranges = $this->get_call_num_ranges();
    foreach($call_num_ranges as $call_num_range) {
      $fund_id     = $call_num_range['fund_id'];
      $start_range = $call_num_range['start_range'];
      $end_range   = $call_num_range['end_range'];

      $array       = array($start_range, $end_range, $call_num);
      $sort        = new sort_lc($array);
      $sorted      = $sort->call_nums();

      // If $call_num falls between the start and end range then return the fund_id
      if($call_num === $sorted[1]) {
        return $fund_id;
      }
    // Assign fund_id of NULL if the call number cannot be parsed appropriately
    } return NULL;
  }
  
  /**
    * Returns array of all the call number ranges with assigned funds
    *
    * @access protected
    * @param NULL
    * @return array Array of all call number ranges with assigned funds
    *
    */
  protected function get_call_num_ranges() {
    // Connect to database
    $database        = new db;
    $db              = $database->connect();
    $sql             = "SELECT fund_id, start_range, end_range FROM call_nums";
    $query           = $db->prepare($sql);
    $query->execute();
    $results         = $query->fetchAll(PDO::FETCH_ASSOC);
    $db              = NULL;
    $call_num_ranges = NULL;
    // Adjust start and end ranges so that true ranges are established for sorting later
    foreach($results as $call_num_range) {
      $fund_id            = $call_num_range['fund_id'];
      $start_range        = $call_num_range['start_range'];
      $adjust_start_range = $this->normalize_call_num($start_range);
      if(is_null($adjust_start_range['class_number'])) {
        $start_range = $start_range . '1';
      }
      $end_range        = $call_num_range['end_range'];
      $adjust_end_range = $this->normalize_call_num($end_range);
      if(is_null($adjust_end_range['class_number'])) {
        $end_range = $end_range . '9999.9999';
      } else if($adjust_end_range['decimal_number'] == '            ') {
        $end_range = $end_range . '.9999';
      }
      $call_num_ranges[] = array('fund_id' => $fund_id, 'start_range' => $start_range, 'end_range' => $end_range);
    }
    return $call_num_ranges;
  }
  
  /**
    * Normalizes the call numbers for easier sorting
    *
    * @access protected
    * @param string Call number
    * @return array Array of parsed and normalized call number
    *
    */
  protected function normalize_call_num($call_num) {
    //Convert all alpha to uppercase
    $lc_call_no = strtoupper($call_num);

    // define special trimmings that indicate integer
    $integer_markers = array("C.","BD.","DISC","DISK","NO.","PT.","V.","VOL.");
    foreach ($integer_markers as $mark) {
      $mark = str_replace(".", "\.", $mark);
      $lc_call_no = preg_replace("/$mark(\d+)/","$mark$1;",$lc_call_no);
    } // end foreach int marker

    // Remove any inital white space
    $lc_call_no = preg_replace ("/\s*/","",$lc_call_no);

    if (preg_match("/^([A-Z]{1,3})\s*(\d+)\s*\.*(\d*)\s*\.*\s*([A-Z]*)(\d*)\s*([A-Z]*)(\d*)\s*(.*)$/",$lc_call_no,$m)) {
      $initial_letters = $m[1];
      $class_number    = $m[2];
      $decimal_number  = $m[3];
      $cutter_1_letter = $m[4];
      $cutter_1_number = $m[5];
      $cutter_2_letter = $m[6];
      $cutter_2_number = $m[7];
      $the_trimmings   = $m[8];
    } //end if call number match

    if ($class_number) {
      $class_number = sprintf("%5s", $class_number);
    }
    $decimal_number = sprintf("%-12s", $decimal_number);
    return array('initial_letters' => $initial_letters, 'class_number' => $class_number, 'decimal_number' => $decimal_number);
  }
  
}

?>
