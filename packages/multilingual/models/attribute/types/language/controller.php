<?php  
defined('C5_EXECUTE') or die("Access Denied.");

Loader::library('3rdparty/Zend/Locale');
Loader::library('content_localization', 'multilingual');

class LanguageAttributeTypeController extends AttributeTypeController  {

	protected $searchIndexFieldDefinition = 'C 11 DEFAULT 0 NULL';

	public function getValue() {		
		$db = Loader::db();
		$value = $db->GetOne("select value from atLanguage where avID = ?", array($this->getAttributeValueID()));
		return $value;
	}
	
	public function getDisplayValue(){
		$lang = $this->getValue();
		$languages = MultilingualContentLocalization::getLanguages();
		return $languages[$lang];	
	}
	
	public function getDisplaySanitizedValue(){
		return $this->getDisplayValue();	
	}
	

	public function searchForm($list) {
		$lang = $this->request('value');
		$list->filterByAttribute($this->attributeKey->getAttributeKeyHandle(), $lang, '=');
		return $list;
	}
	
	public function search() {
		print t('<input type="text" name="%s" value="%s">', $this->field('value'), $this->request('value'));
	}
	
	
	
	public function getAttributeKeyCategory(){
		return AttributeKeyCategory::getByID($this->getAttributeKey()->getAttributeKeyCategoryID());	
	}
	
	public function getAttributeKeyCategoryHandle(){
		return $this->getAttributeKeyCategory()->getAttributeKeyCategoryHandle();
	}
	
	//Get the type of class that owns this attribute 
	public function getValueOwnerClass(){
		//Check the actual owner first
		$valueOwner = $this->getValueOwner();
		if(is_object($valueOwner)){
			return get_class($valueOwner);
		}
		//Fallback to attribute category
		$attrCategoryHandle = $this->getAttributeKeyCategoryHandle();		
		if($attrCategoryHandle == 'collection'){
			$ValueOwnerClass = Page;
		}else if($attrCategoryHandle == 'file'){
			$ValueOwnerClass = File;
		}else if($attrCategoryHandle == 'user'){
			$ValueOwnerClass = UserInfo;	
		}
		return $ValueOwnerClass;
	}
	
	//Get the object that owns this attribute
	public function getValueOwner(){
		$attrCategoryHandle = $this->getAttributeKeyCategoryHandle();
		$oID = $this->getValueOwnerID();
		
		if($attrCategoryHandle == 'collection'){
			return Page::getByID($oID);
		}else if($attrCategoryHandle == 'file'){
			return File::getByID($oID);
		}else if($attrCategoryHandle == 'user'){
			return UserInfo::getByID($oID);	
		}
		
		/*$valueObj = $this->getAttributeValue();
		
		if($attrCategoryHandle == 'collection'){
			return $valueObj->c;
		}else if($attrCategoryHandle == 'file'){
			return $valueObj->f;
		}else if($attrCategoryHandle == 'user'){
			return $valueObj->u;	
		}*/
	}
	
	//Get the ID of the owner object
	public function getValueOwnerID(){
		if($this->valueOwnerID){
			return $this->valueOwnerID;	
		}
		
		$attrCategoryHandle = $this->getAttributeKeyCategoryHandle();		
		$valueObj = $this->getAttributeValue();
		
		if(!is_object($valueObj)) return; //nothin'
		
		//Get the ID from the attribute value object...this should be easier to get to. gross.
		if($attrCategoryHandle == 'collection' && is_object($valueObj->c)){
			return $valueObj->c->getCollectionID(); 
		}else if($attrCategoryHandle == 'file' && is_object($valueObj->f)){
			return $valueObj->f->getFileID();
		}else if($attrCategoryHandle == 'user' && is_object($valueObj->u)){
			return $valueObj->u->getUserID();	
		}
	}
	
	public function form() {
		//Determine what type of owner object we're working with
		$attrCategoryHandle = $this->getAttributeKeyCategoryHandle();
		$this->set('attributeKeyCategoryHandle', $attrCategoryHandle);
		
		$ValueOwnerClass = $this->getValueOwnerClass();
		$this->set('ValueOwnerClass', $ValueOwnerClass);
		
		$this->set('valueOwnerID', $this->getValueOwnerID());
		$this->set('valueOwner', $this->getValueOwner());		
			
		if (is_object($this->attributeValue)) {
			$value = $this->getAttributeValue()->getValue();			
		}
		
		$pkg = Package::getByHandle('multilingual');
		
		$locales = MultilingualContentLocalization::getLanguages();
		asort($locales);
		$this->set('locales', $locales);
		$this->set('defaultLanguage', $pkg->config('DEFAULT_LANGUAGE'));
		$this->set('value', $value);
	}
	
	public function validateForm($p) {
		return $p['value'] != 0;
	}

	public function saveValue($value) {
		$db = Loader::db();
		$db->Replace('atLanguage', array('avID' => $this->getAttributeValueID(), 'value' => $value), 'avID', true);
	}
	
	public function saveRelations($relations){
		
		$ValueOwnerClass = $this->getValueOwnerClass();
		$akID = $this->getAttributeKey()->getAttributeKeyID();
		$relationID = $this->getRelationID();
		$db = Loader::db();
		
		$db->Execute("delete from atLanguageRelations where relationID = ?", array($relationID));
		
		if(count($relations)){
			
			$db->Replace(
				'atLanguageRelations', 
				array(
					'oID' => $this->getValueOwnerID(), 
					'akID' => $akID,
					'relationID' => $relationID				
				),
				array('oID','akID'),
				true
			);
			
			foreach($relations as $relation){
				if(!$relation['oID']) continue;
				
				$relationOwner = $ValueOwnerClass::getByID($relation['oID']);
				
				if($relation['delete'] == 'delete'){
					//do nothing, we already deleted them above
				}else{
					$db->Replace(
						'atLanguageRelations', 
						array(
							'oID' => $relation['oID'], 
							'akID' => $akID,
							'relationID' => $relationID				
						),
						array('oID','akID'),
						true
					);
					if(isset($relation['value'])){
						$relationOwner->setAttribute($this->getAttributeKey(), $relation['value']);
					}
				}
			}
		}
			
	}
	
	public function deleteKey() {
		$db = Loader::db();
		$arr = $this->attributeKey->getAttributeValueIDList();
		foreach($arr as $id) {
			$db->Execute('delete from atLanguage where avID = ?', array($id));
		}
	}
	
	public function saveForm($data) {
		$db = Loader::db();
		$this->saveValue($data['value']);
		$KeyClass = get_class($this->getAttributeKey());
		$ValueClass = get_class($this->getAttributeValue());
		
		//Log::addEntry(print_r($data, true));
		
		if($data['oID']){
			$this->valueOwnerID = $data['oID'];	
		}
		
		if($data['detach'] == 1){
			$this->deleteRelation(); //Remove this from the group
			
		}else if(is_array($data['relation'])){
			$this->saveRelations($data['relation']);
		
		}
		
	}
	
	public function deleteValue() {
		$db = Loader::db();
		$db->Execute('delete from atLanguage where avID = ?', array($this->getAttributeValueID()));
		$this->deleteRelation();
	}
	
	public function deleteRelation(){
		$valueOwnerID = $this->getValueOwnerID();
		if($valueOwnerID){
			$akID = $this->getAttributeKey()->getAttributeKeyID();
			$db = Loader::db();
			$db->Execute('delete from atLanguageRelations where oID = ? and akID = ?', array($valueOwnerID, $akID));	
		}
	}
	
	public function getRelationID($autoCreate = true){
		$db = Loader::db();
		$valueOwnerID = $this->getValueOwnerID();
		$akID = $this->getAttributeKey()->getAttributeKeyID();
		$relationID = $db->GetOne("select relationID from atLanguageRelations where akID = ? and oID = ?", array($akID, $valueOwnerID));	
		if($relationID) {
			return $relationID;	
		}else if($autoCreate){
			$db->Execute('insert into atLanguageRelations (akID, oID) values(?, ?)', array($akID, $valueOwnerID));
			return $db->Insert_ID();	
		}
	}
	
	public function getRelations(){		
		$db = Loader::db();	
		$valueOwnerID = $this->getValueOwnerID();
		$akID = $this->getAttributeKey()->getAttributeKeyID();
		
		$rows = $db->GetAll("select oID, relationID from atLanguageRelations where relationID = (select relationID from atLanguageRelations where oID = ?) and oID != ?", array($valueOwnerID, $valueOwnerID));
		
		$ValueOwnerClass = $this->getValueOwnerClass();
		
		foreach($rows as &$row){			
			$row['owner'] = $ValueOwnerClass::getByID($row['oID']);
			$row['value'] = $row['owner']->getAttribute($this->getAttributeKey()->getAttributeKeyHandle());
		}
	
		return $rows;
	}
	
	public function print_pre($thing, $return=false){
		$out = '<pre style="white-space:pre;">';
		$out .= print_r($thing, true);
		$out .= '</pre>';
		if(!$return){
			echo $out;
			return;	
		}
		return $thing;
	}
	
}


//TODO: clean up the "value owner" mess in the controller above
class LanguageAttributeValueOwner extends Object {
	
	
	public function getAttributeKey($ak){
		
	}
	
	public function setAttributeKey($ak){
		
		if(is_numeric($ak)){
			$ak = AttributeKey::getByID($ak);	
		}else if(is_string($ak)){
			$ak = AttributeKey::getByHandle($ak);	
		}
		
		$this->attributeKey = $ak;
	}
		
}