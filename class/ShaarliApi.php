<?php

class ShaarliApi {
	
	/**
	 * Feeds list
	 * @route /feeds
	 */
	public function feeds( $arguments ) {

		$feeds = Feed::factory();

		// Full feed list
		if( isset($arguments['full']) && $arguments['full'] == 1 ) {

			$feeds->select_expr(RIVER_FEEDS_TABLE.'.*');
		}
		// Disabled feeds
		elseif( isset($arguments['disabled']) && $arguments['disabled'] == 1 ) {

			$feeds->where('enabled', 0);
		}
		// Feeds with error
		elseif( isset($arguments['error']) && $arguments['error'] == 1 ) {

			$feeds->where_not_null('error');
		}
		// Active feeds
		else {

			$feeds->select_expr('id, url, link, title');
			$feeds->where_null('error');
			$feeds->where('enabled', 1);			
		}

		// Filter by feed ids
		if( isset($arguments['ids']) && !empty($arguments['ids']) && is_array($arguments['ids']) ) {

			foreach($arguments['ids'] as $id){
				if( !is_numeric($id) ) {
					throw new \Exception("Error Processing Request");	
				}
			}

			$feeds->where_in('id', $arguments['ids'] );
		}

		return $feeds->findArray();
	}

	/**
	 * Latest entries
	 * @route /latest
	 */
	public function latest( $arguments ) {

		$entries = Feed::factory()
					 ->select_expr(RIVER_FEEDS_TABLE.'.id AS feed_id, ' .RIVER_FEEDS_TABLE .'.url AS feed_url, '. RIVER_FEEDS_TABLE. '.link AS feed_link, '. RIVER_FEEDS_TABLE . '.title AS feed_title')
					 ->select_expr(RIVER_ENTRIES_TABLE.'.id, date, permalink, ' . RIVER_ENTRIES_TABLE. '.title, content, categories')
					 ->join(RIVER_ENTRIES_TABLE, array(RIVER_ENTRIES_TABLE.'.feed_id', '=', RIVER_FEEDS_TABLE.'.id'))
					 ->order_by_desc('date');

		// Count reshare links
		if( isset($arguments['reshare']) && $arguments['reshare'] == 1 ) {

			$entries->select_expr('(SELECT COUNT(1) FROM' . RIVER_FEEDS_TABLE . 'AS e2 WHERE '.RIVER_ENTRIES_TABLE.'.permalink=e2.permalink) AS shares');
		}

		// Filter by feed ids
		if( isset($arguments['ids']) && !empty($arguments['ids']) && is_array($arguments['ids']) ) {

			foreach($arguments['ids'] as $id){
				if( !is_numeric($id) ) {
					throw new \Exception("Error Processing Request");	
				}
			}

			$entries->where_in(RIVER_FEEDS_TABLE.'.id', $arguments['ids'] );
		}

		// Limit
		if( isset($arguments['limit']) && $arguments['limit'] >= 5 && $arguments['limit'] <= ENTRIES_DISPLAYED ) {
			$entries->limit( (int) $arguments['limit'] );
		}
		else {
			$entries->limit(ENTRIES_DISPLAYED);
		}

		$entries = $entries->findArray();

		if( $entries != null ) {

			foreach( $entries as &$entry ) {

				$entry['feed']['id'] = $entry['feed_id'];
				$entry['feed']['url'] = $entry['feed_url'];				
				$entry['feed']['link'] = $entry['feed_link'];
				$entry['feed']['title'] = $entry['feed_title'];

				unset($entry['feed_id'], $entry['feed_url'], $entry['feed_link'], $entry['feed_title']);
			}
		}

		return $entries;
	}

	/**
	 * Top shared links
	 * @route /top
	 * @args interval={interval}
	 */
	public function top( $arguments ) {

		$intervals = array(
			'12h',
			'24h',
			'48h',
			'1month',
			'3month',
			'alltime',
		);

		if( isset($arguments['interval']) ) {

			if( in_array($arguments['interval'], $intervals) ) {

                $entries = Entry::factory();
                if(DB_TYPE!="pgsql") {
                    $entries = $entries
                        ->select_expr('permalink, '.RIVER_ENTRIES_TABLE.'.title, COUNT(1) AS count')
                        ->order_by_desc('count')
                        ->group_by('permalink')
                        ->having_gt('count', 1);
                }

				if(DB_TYPE=="sqlite"){
					switch ($arguments['interval']) {
						case '12h':					
							$entries->where_raw("date > date('now', '-12 hour')");
							break;
						case '24h':					
							$entries->where_raw("date > date('now', '-24 hour')");
							break;
						case '48h':
							$entries->where_raw("date > date('now', '-48 hour')");
							break;
						case '1month':					
							$entries->where_raw("date > date('now', '-1 month')");
							break;
						case '3month':					
							$entries->where_raw("date > date('now', '-3 month')");
							break;
					}
				}elseif(DB_TYPE=='mysql'){
					switch ($arguments['interval']) {
						case '12h':					
							$entries->where_raw('date > ADDDATE(NOW(), INTERVAL -12 HOUR)');
							break;
						case '24h':					
							$entries->where_raw('date > ADDDATE(NOW(), INTERVAL -24 HOUR)');
							break;
						case '48h':
							$entries->where_raw('date > ADDDATE(NOW(), INTERVAL -48 HOUR)');
							break;
						case '1month':					
							$entries->where_raw('date > ADDDATE(NOW(), INTERVAL -1 MONTH)');
							break;
						case '3month':					
							$entries->where_raw('date > ADDDATE(NOW(), INTERVAL -3 MONTH)');
							break;
					}
				}
                elseif(DB_TYPE=='pgsql') {
                    $pgsql_query = "SELECT a.permalink, ".
                        "(select b.title from " . RIVER_ENTRIES_TABLE . " b where b.permalink = a.permalink order by b.id asc limit 1), COUNT(1) ".
                        "FROM " . RIVER_ENTRIES_TABLE . " a WHERE (a.date > NOW() - interval '%s') group by a.permalink HAVING COUNT(1) > 1 order by COUNT(1) desc";
                    switch ($arguments['interval']) {
                        case '12h':
                            $entries->raw_query(sprintf($pgsql_query, '12 HOURS'));
                            break;
                        case '24h':
                            $entries->raw_query(sprintf($pgsql_query, '24 HOURS'));
                            break;
                        case '48h':
                            $entries->raw_query(sprintf($pgsql_query, '48 HOURS'));
                            break;
                        case '1month':
                            $entries->raw_query(sprintf($pgsql_query, '1 MONTH'));
                            break;
                        case '3month':
                            $entries->raw_query(sprintf($pgsql_query, '3 MONTHS'));
                            break;
                    }
                }
                else
                {
					die("Error in config.php. DB_TYPE is not sqlite, mysql or pgsql");
				}

				return $entries->findArray();
			}
			else {

				throw new ShaarliApiException('Invalid interval (?interval={' . implode('|', $intervals) . '})');				
			}
		}
		elseif( isset($arguments['date']) && !empty($arguments['date'])) {

			// TODO parse date
			$date = date('Y-m-d', strtotime('-1 days'));

			$results = array();

			$entries = Entry::factory();
            if(DB_TYPE != 'pgsql') {
                $entries->select_expr('permalink, ' . RIVER_ENTRIES_TABLE . '.title, COUNT(1) AS count')
                    ->where_raw('DATE(' . RIVER_ENTRIES_TABLE . '.date)=?', array($date))
                    ->order_by_desc('count')
                    ->group_by('permalink')
                    ->having_gt('count', 1);
            }
            else {
                $pgsql_query = "SELECT a.permalink, ".
                    "(select b.title from " . RIVER_ENTRIES_TABLE . " b where b.permalink = a.permalink order by b.id asc limit 1), COUNT(1) ".
                    "FROM " . RIVER_ENTRIES_TABLE . " a WHERE DATE(a.date) = '%s' group by a.permalink HAVING COUNT(1) > 1 order by COUNT(1) desc";
                $entries->raw_query(sprintf($pgsql_query, $date));
            }

			$entries = $entries->findArray();

			$results['date'] = $date;
			$results['entries'] = $entries;

			return $results;
		}
	}

	/**
	 * Best reshared links
	 */
	public function bestlinks( $arguments ) {

		$entries = Feed::factory()
				->select_expr(RIVER_FEEDS_TABLE .'.id AS feed_id, ' . RIVER_FEEDS_TABLE . '..url AS feed_url, ' . RIVER_FEEDS_TABLE . '.link AS feed_link, ' . RIVER_FEEDS_TABLE . '.title AS feed_title')
				->select_expr('MIN(' . RIVER_ENTRIES_TABLE . '.date) AS date, permalink, ' . RIVER_ENTRIES_TABLE . '.title, content, COUNT(1) AS count')
				->join(RIVER_ENTRIES_TABLE, array(RIVER_ENTRIES_TABLE .'.feed_id', '=', RIVER_ENTRIES_TABLE .'.id'))
				->order_by_expr('MIN(' . RIVER_ENTRIES_TABLE . '.date) DESC')
				->group_by('permalink')
				->having_raw('count > 1'); // TODO group order wtf

		$entries = $entries->findArray();

		if( $entries != null ) {

			foreach( $entries as &$entry ) {

				$entry['feed']['id'] = $entry['feed_id'];
				$entry['feed']['url'] = $entry['feed_url'];
				$entry['feed']['link'] = $entry['feed_link'];
				$entry['feed']['title'] = $entry['feed_title'];

				unset($entry['feed_id'], $entry['feed_url'], $entry['feed_link'], $entry['feed_title']);
			}
		}

		return $entries;
	}

	/**
	 * Search entries
	 * @route /search
	 * @args q={searchterm}
	 */
	public function search( $arguments ) {

		if( isset($arguments['q']) && !empty($arguments['q']) ) {

			$term = $arguments['q'];

			// Advanced Search
			$adv_search = null;

			if( preg_match('/^title\:/i', $term) ) {

				$term = preg_replace('/^title\:/', '', $term);
				$adv_search = 'title';
			}
			elseif( preg_match('/^feed\:/i', $term) ) {

				$term = preg_replace('/^feed\:/', '', $term);
				$adv_search = 'feed';
			}
            elseif( preg_match('/^tag\:/i', $term) ) {
                $term = preg_replace('/^tag\:/', '', $term);
                $adv_search = 'tag';
            }

			$term = '%' . $term . '%';

			$entries = Feed::factory()
					->select_expr(RIVER_FEEDS_TABLE .'.id AS feed_id, ' . RIVER_FEEDS_TABLE . '.url AS feed_url, ' . RIVER_FEEDS_TABLE . '.link AS feed_link, ' . RIVER_FEEDS_TABLE . '.title AS feed_title')
					->select_expr(RIVER_ENTRIES_TABLE.'.id, date, permalink, ' . RIVER_ENTRIES_TABLE . '.title, content, categories')
					->join(RIVER_ENTRIES_TABLE, array(RIVER_ENTRIES_TABLE.'.feed_id', '=', RIVER_FEEDS_TABLE . '.id'))
					->order_by_desc('date');

			if( $adv_search == 'title' ) { // Search in title only
				$entries->where_like(RIVER_ENTRIES_TABLE . '.title', $term);
			}
			elseif ( $adv_search == 'feed' ) {
			    $entries->where_like(RIVER_FEEDS_TABLE .'.title', $term);
			}
            elseif( $adv_search == 'tag' ) {
                $entries->where_like(RIVER_ENTRIES_TABLE . '.categories', $term);
            }
			else {

				$entries->where_raw('(' .RIVER_ENTRIES_TABLE . '.title LIKE ? OR ' .RIVER_ENTRIES_TABLE . '.content LIKE ? OR ' . RIVER_FEEDS_TABLE . '.title LIKE ?)', array($term, $term, $term)); // security: possible injection?
			}

			// Filter by feed ids
			if( isset($arguments['ids']) && !empty($arguments['ids']) && is_array($arguments['ids']) ) {

				foreach($arguments['ids'] as $id){
					if( !is_numeric($id) ) {
						throw new \Exception("Error Processing Request");	
					}
				}

				$entries->where_in(RIVER_FEEDS_TABLE.'.id', $arguments['ids'] );
			}

			$entries = $entries->findArray();
            if( $entries != null ) {

                foreach( $entries as $key => &$entry ) {
                    if( $adv_search == 'tag' && !in_array(substr($term,1,strlen($term)-2), explode(',', $entry['categories']))) {
                        unset($entries[$key]);
                        continue;
                    }

					$entry['feed']['id'] = $entry['feed_id'];
					$entry['feed']['url'] = $entry['feed_url'];
					$entry['feed']['link'] = $entry['feed_link'];
					$entry['feed']['title'] = $entry['feed_title'];

					unset($entry['feed_id'], $entry['feed_url'], $entry['feed_link'], $entry['feed_title']);
				}
			}

			return $entries;
		}
		else {

			throw new ShaarliApiException('Need search term (?q=searchterm)');
		}
	}

	/**
	 * Random entries
	 * @route /random
	 */
	public function random( $arguments ) {

		$entries = Feed::factory()
					 ->select_expr(RIVER_FEEDS_TABLE.'.id AS feed_id, ' . RIVER_FEEDS_TABLE . '.url AS feed_url, '. RIVER_FEEDS_TABLE . '.link AS feed_link, ' . RIVER_FEEDS_TABLE . '.title AS feed_title')
					 ->select_expr(RIVER_ENTRIES_TABLE . '.id, date, permalink, ' . RIVER_ENTRIES_TABLE . '.title, content, categories')
					->join(RIVER_ENTRIES_TABLE, array(RIVER_ENTRIES_TABLE .'.feed_id', '=', RIVER_FEEDS_TABLE . '.id'))
					->order_by_expr('RAND()');

		// Limit
		if( isset($arguments['limit']) && $arguments['limit'] >= 5 && $arguments['limit'] <= 100 ) {
			$entries->limit( (int) $arguments['limit'] );
		}
		else {
			$entries->limit(50);
		}

		$entries = $entries->findArray();

		if( $entries != null ) {

			foreach( $entries as &$entry ) {

				$entry['feed']['id'] = $entry['feed_id'];
				$entry['feed']['url'] = $entry['feed_url'];				
				$entry['feed']['link'] = $entry['feed_link'];
				$entry['feed']['title'] = $entry['feed_title'];

				unset($entry['feed_id'], $entry['feed_url'], $entry['feed_link'], $entry['feed_title']);
			}
		}

		return $entries;
	}

	/**
	 * Search linked entries
	 * @route /discussion
	 * @args url={url}
	 */
	public function discussion( $arguments ) {

		if( isset($arguments['url']) && !empty($arguments['url']) ) {

			$search_method = 'strict';

			$url = trim($arguments['url']);

			$entries = Feed::factory()
					->select_expr(RIVER_FEEDS_TABLE . '.id AS feed_id, ' . RIVER_FEEDS_TABLE . '.url AS feed_url, ' . RIVER_FEEDS_TABLE . '.link AS feed_link, ' . RIVER_FEEDS_TABLE . '.title AS feed_title')
					->select_expr(RIVER_ENTRIES_TABLE . '.id, date, permalink, ' .RIVER_ENTRIES_TABLE . '.title, content, categories')
					->join(RIVER_ENTRIES_TABLE, array(RIVER_ENTRIES_TABLE .'.feed_id', '=', RIVER_FEEDS_TABLE . '.id'))
					->order_by_desc('date');

			if( $search_method == 'strict' ) {

				$entries->where(RIVER_ENTRIES_TABLE .'.permalink', $url);
			}
			else if( $search_method == 'large' ) {

				// TODO: à réfléchir...
				$url = trim($url, '/') . '%';
				$entries->where_like(RIVER_ENTRIES_TABLE . '.permalink', $url);
			}

			$entries = $entries->findArray();

			if( $entries != null ) {

				foreach( $entries as &$entry ) {

					$entry['feed']['id'] = $entry['feed_id'];
					$entry['feed']['url'] = $entry['feed_url'];
					$entry['feed']['link'] = $entry['feed_link'];
					$entry['feed']['title'] = $entry['feed_title'];

					unset($entry['feed_id'], $entry['feed_url'], $entry['feed_link'], $entry['feed_title']);
				}
			}

			return $entries;
		}
		else {

			throw new ShaarliApiException('Need url (?url=url)');
		}
	}

	/**
	 * Keywords list
	 * @route /keywords
	 */
	public function keywords() {

		$entries = Entry::factory()
			->select_expr('categories')
			->where_raw('categories IS NOT NULL')
			->findArray();

		$keywords = array();

		if( !empty($entries) ) {

			foreach( $entries as $entry ) {

				$categories = explode(',', $entry['categories']);

				if( !empty($categories) ) {

					foreach( $categories as $categorie ) {

						$categorie = trim($categorie);
						$categorie = ltrim($categorie, '#');

						if( !empty($categorie) ) {

							if( isset($keywords[$categorie]) ) {
								$keywords[$categorie]++;
							}
							else {
								$keywords[$categorie] = 1;
							}							
						}
					}
				}
			}

			arsort($keywords);
		}

		return $keywords;
	}

	/**
	 * Sync feeds list with other API nodes
	 * @param array nodes
	 */
	public function syncfeeds( $nodes ) {

		if( !empty($nodes) ) {

			foreach( $nodes as $node ) {

				$content = file_get_contents($node);
				$rows = json_decode($content);

				if( !empty($rows) ) {

					$urls = array();

					foreach( $rows as $row ) {

						if( isset($row->url) && !empty($row->url) ) {

							$urls[] = $row->url;
						}
					}

					if( !empty($urls) ) {

						$this->addFeeds( $urls );
					}
				}
			}			
		}
	}

	/**
	 * Sync with OPML files
	 * @param array files
	 */
	public function syncWithOpmlFiles( $files ) {

		if( !empty($files) ) {

			foreach( $files as $file ) {

			    $body = file_get_contents($file);	

			    if( !empty($body) ) {

			    	$urls = array();

			    	// Code from Oros, thanks!
				    $xml = @simplexml_load_string($body);

				    foreach ($xml->body->outline as $value) {

				        $attributes = $value->attributes();

				        $urls[] = $attributes->xmlUrl;
				    }

			    	if( !empty($urls) ) {

						$this->addFeeds( $urls );
					}
			    }	
			}			
		}
	}

	/**
	 * Push feed url list in database
	 * @param array urls
	 */
	public function addFeeds( $urls ) {

		if( !empty($urls) ) {

			$urls = array_unique($urls);

			foreach( $urls as $url ) {

				$feed = Feed::create();
				$feed->url = $url;

				if( !$feed->exists() ) {

					$feed->save();
				}
			}
		}
	}

	/**
	 * Favicon service
	 * @route /getfavicon
	 * @args id={feed_id}
	 */
	public function getfavicon( $arguments ) {

		if( isset($arguments['id']) && !empty($arguments['id']) ) {

			if( is_numeric($arguments['id']) && $arguments['id'] > 0 ) {

				$favicon = FAVICON_DIRECTORY . $arguments['id'] . '.ico';

				 if( !file_exists($favicon) ) {
				 	$favicon = __DIR__ . '/favicon.ico';
				 }

				return array('favicon' => $favicon);
			}
		}
	}

    /**
     * Count links
     * @route /countlinks
     */
    public function countlinks() {
        return array('total' => Entry::factory()->count());
    }

	/**
	 * Ping service
	 * @route /ping
	 * @args url={url}
	 */
	public function ping( $arguments ) {

		if( isset($arguments['url']) && !empty($arguments['url']) ) {

			$json = array( 'success' => 0 );

			$feed = Feed::findByUrl( $arguments['url'] );

			if( $feed != null ) {

				$feed->fetched_at = null;
				$feed->save();

				$json['success'] = 1;	
			}

			return $json;
		}
		else {

			throw new ShaarliApiException('Need url (?url=url)');
		}		
	}
}
