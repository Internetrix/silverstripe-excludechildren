<?php

class LeftAndMainExcludesExtension extends DataExtension {
	
	private static $allowed_actions = array(
			'updatetreenodesexclude'
	);
	
	public function updatetreenodesexclude($request) {
		$data = array();
		$ids = explode(',', $request->getVar('ids'));
		foreach($ids as $id) {
			if($id === "") continue; // $id may be a blank string, which is invalid and should be skipped over

			$record = $this->owner->getRecord($id);
			if(!$record) continue; // In case a page is no longer available
			$recordController = ($this->owner->stat('tree_class') == 'SiteTree')
				?  singleton('CMSPageEditController')
				: $this->owner;

			// Find the next & previous nodes, for proper positioning (Sort isn't good enough - it's not a raw offset)
			// TODO: These methods should really be in hierarchy - for a start it assumes Sort exists
			$next = $prev = null;

			$className = $this->owner->stat('tree_class');
/*			$next = DataObject::get($className)
				->filter('ParentID', $record->ParentID)
				->filter('Sort:GreaterThan', $record->Sort)
				->first();

			if (!$next) {
				$prev = DataObject::get($className)
					->filter('ParentID', $record->ParentID)
					->filter('Sort:LessThan', $record->Sort)
					->reverse()
					->first();
			}
*/
			$parent = $record->Parent();
			$next = DataObject::get($className)
				 ->filter('ParentID', $record->ParentID)
				 ->filter('Sort:GreaterThan', $record->Sort);
			
			if ($next) {
				if ($parent->hasMethod("getExcludedClasses")){
					$next = $next->exclude('ClassName', $parent->getExcludedClasses())->first();
				} else {
					$next = $next->first();
				}
			} else {
				$prev = DataObject::get($className)
				->filter('ParentID', $record->ParentID)
				->filter('Sort:LessThan', $record->Sort)
				->reverse();
			}
				
			if ($prev) {
				if ($parent->hasMethod("getExcludedClasses")){
					$prev = $prev->exclude('ClassName', $parent->getExcludedClasses())->first();
				} else {
					$prev = $prev->first();
				}
			}
				
			$link = Controller::join_links($recordController->Link("show"), $record->ID);
			$html = LeftAndMain_TreeNode::create($record, $link, $this->owner->isCurrentPage($record))
				->forTemplate() . '</li>';

			$data[$id] = array(
				'html' => $html,
				'ParentID' => $record->ParentID,
				'NextID' => $next ? $next->ID : null,
				'PrevID' => $prev ? $prev->ID : null
			);
		}
		$this->owner->response->addHeader('Content-Type', 'text/json');
		return Convert::raw2json($data);
	}
	
}

