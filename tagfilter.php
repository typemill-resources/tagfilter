<?php

namespace Plugins\Tagfilter;

use \Typemill\Plugin;
use \Typemill\Models\Write;
use \Typemill\Models\WriteCache;
use \Typemill\Models\WriteYaml;

class Tagfilter extends Plugin
{

	protected $settings;
	protected $item;

    public static function getSubscribedEvents()
    {
		return array(
			'onSettingsLoaded' 		=> 'onsettingsLoaded',
			'onItemLoaded' 			=> 'onItemLoaded',
			'onPagetreeLoaded'		=> 'onPagetreeLoaded',
			'onTwigLoaded'			=> 'onTwigLoaded',
			'onMetaLoaded'			=> 'onMetaLoaded',
			'onContentArrayLoaded' 	=> 'onContentArrayLoaded',
			'onPagePublished'		=> 'onPagePublished',
			'onPageUnpublished'		=> 'onPageUnpublished',
			'onPageSorted'			=> 'onPageSorted',
			'onPageDeleted'			=> 'onPageDeleted',
		);
	}

	# add the path stored in user-settings to initiate session and csrf-protection
	public function onSettingsLoaded($settings)
	{
		$this->settings = $settings->getData();
	}
	
	public function onItemLoaded($item)
	{
		$this->item = $item->getData();
	}

	public function onPagetreeLoaded($pagetree)
	{
		$this->pagetree = $pagetree->getData();
	}

    public function onTwigLoaded()
    {
        $this->addEditorJS('/tagfilter/js/tagfiltertab.js');
    }

	public function onMetaLoaded($metadata)
	{
		$this->meta = $metadata->getData();
	}

	# at any of theses events, delete the cached page data	
	public function onPagePublished($item)
	{
		$this->deletePageData();
	}
	public function onPageUnpublished($item)
	{
		$this->deletePageData();
	}
	public function onPageSorted($inputParams)
	{
		$this->deletePageData();
	}
	public function onPageDeleted($item)
	{
		$this->deletePageData();
	}

	private function deletePageData()
	{
    	$write = new Write();

    	# delete the index-file for pages
    	$write->deleteFileWithPath('cache' . DIRECTORY_SEPARATOR . 'pagedata.txt');

    	# delete the taglist
    	$write->deleteFileWithPath('cache' . DIRECTORY_SEPARATOR . 'taglist.txt');
	}
	
	public function onContentArrayLoaded($contentArray)
	{
		$this->addCSS('/tagfilter/css/tagfilter.css');

		$content 	= $contentArray->getData();

		$filterpage 	= ( isset($this->settings['settings']['plugins']['tagfilter']['filterpage']) && $this->settings['settings']['plugins']['tagfilter']['filterpage'] != '' ) ? $this->settings['settings']['plugins']['tagfilter']['filterpage'] : '/';

		if($this->path == trim($filterpage, '/'))
		{
			# activate axios and vue in frontend
			$this->activateAxios();
			$this->activateVue();

			# check if there are tags in the url
			$params 	= $this->getParams();
			$tag 		= isset($params['tag']) ? $params['tag'] : false;
			if($tag)
			{
				$tag 	= urldecode($tag); 
			}
			
			if( $tag && (( strlen($tag) > 50 ) OR ( $tag != strip_tags($tag) )) )
			{ 
				$tag = false; 
			}

			$pagedata 	= false;
			$cache 		= false;

			# if cache is enabled
			if(isset($this->settings['settings']['plugins']['tagfilter']['cache']))
			{
				$cache 		= new WriteCache();
				$pagedata 	= $cache->getCache('cache', 'pagedata.txt');
			}

			if(!$pagedata)
			{
				# get the page data
				$pagedata 	= $this->getPageData($this->pagetree, []);

				if($cache)
				{
					$cache->updateCache('cache', 'pagedata.txt', false, $pagedata);
				}
			}

			$taglist = false;

			if($cache)
			{
				$taglist = $cache->getCache('cache', 'taglist.txt');
			}

			if(!$taglist)
			{
				$taglist = [];

				foreach($pagedata as $pagemeta)
				{
					if(isset($pagemeta['tags']) && !empty($pagemeta['tags']))
					{
						foreach($pagemeta['tags'] as $category => $tags)
						{
							if(isset($taglist[$category]))
							{
								$taglist[$category] = array_unique (array_merge ($taglist[$category], $tags));
							}
							else
							{
								$taglist[$category] = $tags;
							}
						}
					}
				}

				if($cache)
				{
					$cache->updateCache('cache', 'taglist.txt', false, $taglist);
				}
			}

			# you can define a custom ordner for categories
			$customorder 	= ( isset($this->settings['settings']['plugins']['tagfilter']['customorder']) && $this->settings['settings']['plugins']['tagfilter']['customorder'] != '' ) ? $this->settings['settings']['plugins']['tagfilter']['customorder'] : false;
			if($customorder)
			{
				$customorder = array_map('trim', explode(',', $customorder));
				
				$orderedcategories = [];
				foreach($customorder as $key => $category)
				{
					$orderedcategories[$category] = [];
				}
				
				$taglist = array_merge($orderedcategories,$taglist);
			}

			# create div for vue app
			$resultlabel 	= ( isset($this->settings['settings']['plugins']['tagfilter']['resultlabel']) && $this->settings['settings']['plugins']['tagfilter']['resultlabel'] != '' ) ? $this->settings['settings']['plugins']['tagfilter']['resultlabel'] : 'Number of results';
			$directory 		= '<script>var pages = ' . json_encode($pagedata) . '; var taglist = ' . json_encode($taglist) . '; var tag = "' . htmlentities($tag) . '"; var resultlabel = "' . $resultlabel . '";</script>';
			$directory 		.= '<div id="pagefilterapp" v-cloak>';
			$directory 		.= '<pagefilter></pagefilter></div>';

			# create content type
			$directory = Array
			(
				'rawHtml' 					=> $directory,
				'allowRawHtmlInSafeMode' 	=> true,
				'autobreak' 				=> 1
			);

			$content[] = $directory;
			
			# add the vue app
			$this->addJS('/tagfilter/js/tagfilterapp.js');

		}
		else
		{
			# integrate the tags into the page
			$meta = $this->meta;
			
			$taglist 	= [];

			if(isset($meta['tags']['tags']))
			{
				foreach($meta['tags']['tags'] as $category => $tags)
				{
					$taglist[$category] = array_map('trim', explode(',', $tags));
				}
			}

			if(!empty($taglist))
			{
				$home = $this->container['request']->getUri()->getBaseUrl();

				# create tag markup
				$tagMarkup = '<div class="tags f6">';
				foreach($taglist as $category)
				{
					# do we need to show the category, too? 
					foreach($category as $tag)
					{
						$tagMarkup .= '<a class="tag link black patags matags ba br1 b--black hover-white hover-bg-black pointer" href="' . $home . $filterpage . '?tag=' . urlencode($tag) . '">' . $tag . '</a>';
					}
				}
				$tagMarkup .= '</div>';

				# create content type
				$tagline = Array
				(
					'rawHtml' => $tagMarkup,
					'allowRawHtmlInSafeMode' => true,
					'autobreak' => 1
				);

				# enter tag markup in position
				$length 		= count($content);
				$position		= isset($this->settings['position']) ? $this->settings['position'] : 1;

				if($length > $position)
				{
					$start 		= array_slice($content, 0, $position);
					$end 		= array_slice($content, $position);
					$content 	= array_merge( $start, array($tagline), $end );
				}
				else
				{
					$content[] 	= $tagline;
				}
			}
		}
	
		$contentArray->setData($content);
	}

	private function getPageData($pagetree, $pagedata)
	{
		$writeYaml = new WriteYaml();

		foreach($pagetree as $item)
		{
    		if($item->elementType == "folder")
    		{
	    		$pagedata = $this->getPageData($item->folderContent, $pagedata);
    		}
    		else
    		{
				$meta = $writeYaml->getYaml('content', $item->pathWithoutType . '.yaml');

				$title = isset($meta['meta']['title']) ? $meta['meta']['title'] : 'title not set';
				$description = isset($meta['meta']['description']) ? $meta['meta']['description'] : 'description not set';

				$taglist = [];

				if(isset($meta['tags']['tags']))
				{
					foreach($meta['tags']['tags'] as $category => $tags)
					{
						$taglist[$category] = array_map('trim', explode(',', $tags));
					}
				}
				if(!empty($taglist))
				{
	    			$pagedata[]	= [
			            'urlRelWoF'		=> $item->urlRelWoF,
			            'urlRel' 		=> $item->urlRel,
			            'urlAbs' 		=> $item->urlAbs,
			            'title' 		=> $title,
			            'description'	=> $description,
			            'tags'			=> $taglist,
	    			];
				}
    		}
		}
    	return $pagedata;
	}
}