<?php defined('C5_EXECUTE') or die(_("Access Denied."));

$form = Loader::helper('form');
$htmlId = uniqid('ccm-locale-attr');

$pageSelector = Loader::helper('form/page_selector');
//$pageSelector->selectPage($this->field('value'), $this->request('value'), false);

$assetLibrary = Loader::helper('concrete/asset_library');

?>

<div id="<?php echo $htmlId ?>">
	<?php echo $form->hidden($this->controller->field('oID'), $valueOwnerID); ?>
    
	<?php echo $form->select($this->controller->field('value'), array_merge(array(''=>t('Choose Language')), $locales), $value); ?> 
	
    <button class="btn translations"><?php echo t('Manage Translations') ?></button>
    
	<?php //$this->controller->print_pre($this->controller->getRelations()); ?>
  	<div class="translations" style="display:none; margin:1em 0; padding:.5em; border:1em solid rgba(0,0,0,.1);">
    <table style="width:100%;">        
        <thead>
        	<tr>
                <th colspan="2" style="text-align:left;">Translations</th>
                <th style="text-align:center"><button class="btn add">Add</button></th>
            </tr>
        </thead>
        <tbody>        
        
		<?php foreach($this->controller->getRelations() as $index=>$relation){ ?>
    
        <tr>
            <td><?php echo $form->select($this->controller->field('relation').'['.$relation[$index].'][value]', array_merge(array(''=>t('Choose Language')), $locales), $relation['value']); ?></td>
            <td style="padding:.25em 1em;">
			<?php if($attributeKeyCategoryHandle == 'file'){ 
                echo $assetLibrary->file(uniqid('ccm-file-akID'), $this->controller->field('relation').'['.$relation['avID'].'][oID]', t('Choose File'), $relation['owner']);
            }?>
            </td>
            <td style="text-align:center"><button class="btn remove">Remove</button></td>
        </tr>
		<?php } ?>
        <tr class="add-relation">
        	<td><?php echo $form->select($this->controller->field('relation').'[x][value]', array_merge(array(''=>t('Choose Language')), $locales)); ?></td>
            <td style="padding:.25em 1em;">
			<?php if($attributeKeyCategoryHandle == 'file'){ 
                echo $assetLibrary->file(uniqid('ccm-file-akID'), $this->controller->field('relation').'[x][oID]', t('Choose File'), null);
            }?>
            </td>
            <td style="text-align:center"><button class="btn remove">Remove</button></td>
        </tr>
        </tbody>
    </table>
   </div>
   
</div>
		
<script>
 $(function(){
	var $wrap = $('#<?php echo $htmlId ?>'),
		addHtml = $wrap.find('tr.add-relation').remove().html();
	
	$wrap.on('click', 'button.translations', function(){
		$wrap.find('div.translations').slideToggle();	
	});
	
	$wrap.on('click', 'button.remove', function(){
		$(this).closest('tr').remove();//.hide().find('[name$="[value]"]').val('delete');	
	});
	
	$wrap.on('click', 'button.add', function(){
		var newHtml = addHtml
			.replace(/ccm-file-akID/g, 'ccm-file-akID'+$.now())
			.replace(/\[x\]/g, '['+$wrap.find('table tbody tr').length+']');
		$wrap.find('table tbody').prepend('<tr>'+newHtml+'</tr>');
	});
 });
</script>
		