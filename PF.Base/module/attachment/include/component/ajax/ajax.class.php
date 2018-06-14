<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Attachment_Component_Ajax_Ajax extends Phpfox_Ajax
{	
	public function upload()
	{
		Phpfox::getBlock('attachment.upload', array(
				'sCategoryId' => $this->get('category_id')
			)
		);
	
		$this->call('$("#js_attachment_content").html("' . $this->getContent() . '");');
		$this->call("$('#swfUploaderContainer').css('top',70).css('z-index',880);");
		$this->call('$Core.loadInit();');
	}
	
	public function add()
	{
		if ($this->get('attachment_custom') == 'photo')
		{
			$this->setTitle(_p('attach_a_photo'));
		}
		elseif ($this->get('attachment_custom') == 'video')
		{
			$this->setTitle(_p('attach_a_video'));
		}
		else 
		{
			$this->setTitle(_p('attach_a_file'));
		}
				
				
		$aParams = array(
				'sAttachments' => $this->get('attachments'),
				'sCategoryId' => $this->get('category_id'),
				'iItemId' => $this->get('item_id'),
				'sAttachmentInput' => $this->get('input'),
                'attachment_custom' => $this->get('attachment_custom')
			);
		Phpfox::getBlock('attachment.add', $aParams);
	}
	
	public function browse()
	{
		Phpfox::getBlock('attachment.archive', array('sPage' => (int)$this->get('page')));
		$this->call('$("#js_attachment_content").html("' . $this->getContent() . '");');
		$this->call("$('#swfUploaderContainer').css('top',0).css('z-index',0);");
		
	}
	
	public function updateDescription()
	{
		if (($iUserId = Phpfox::getService('attachment')->hasAccess($this->get('iId'), 'delete_own_attachment', 'delete_user_attachment')) && Phpfox::getService('attachment.process')->updateDescription((int) $this->get('iId'), $iUserId, $this->get('info')))
		{
			$this->html('#js_description' . $this->get('iId'), Phpfox::getLib('parse.output')->clean(Phpfox::getLib('parse.input')->clean($this->get('info'))), '.highlightFade()');
		}
	}

    /**
     * @deprecated since 4.6.0
     */
	public function inline()
	{
        Phpfox::getService('attachment.process')->updateInline($this->get('id'));
	}

    /**
     * @deprecated since 4.6.0
     */
	public function inlineRemove()
	{
		if (Phpfox::getService('attachment.process')->updateInline($this->get('id'), true))
		{
			$sTxt = htmlspecialchars_decode($this->get('text'));
			$sTxt = preg_replace('/\[attachment="' . (int) $this->get('id') . ':(.*)"\](.*)\[\/attachment\]/i', '', $sTxt);
			$sTxt = preg_replace('/\[attachment="' . (int) $this->get('id') . '"\](.*)\[\/attachment\]/i', '', $sTxt);
			$sTxt = str_replace("'", "\\'", $sTxt);
			$sTxt = str_replace('"', '\\"', $sTxt);
			$this->call('Editor.setContent("' . $sTxt . '");');
		}
	}

    public function delete()
    {
        $iUserId = Phpfox::getService('attachment')->hasAccess($this->get('id'), 'delete_own_attachment',
            'delete_user_attachment');
        if ($iUserId && is_numeric($iUserId) &&
            Phpfox::getService('attachment.process')->delete($iUserId, $this->get('id'))
        ) {
            $sEditorHolder = $this->get('editorHolderId');
            if ($sEditorHolder) {
                $this->remove("#js_attachment_id_{$this->get('id')}', '#{$this->get('editorHolderId')}")
                    ->call("typeof \$Core.Attachment !== 'undefined' && \$Core.Attachment.descreaseCounter('{$this->get('editorHolderId')}');");
            } else {
                $this->call("typeof \$Core.Attachment !== 'undefined' && \$Core.Attachment.descreaseCounter(\$Core.Attachment.getEditorHolder('#js_attachment_id_{$this->get('id')}', true));")
                    ->remove("#js_attachment_id_{$this->get('id')}")
                    ->call('$Core.checkAttachmentHolder();');
            }
        }
    }
	
	public function updateActivity()
	{
        Phpfox::getService('attachment.process')->updateActivity($this->get('id'), $this->get('active'));
	}

	public function addViaLink()
	{
		Phpfox::isUser(true);
		
		$aVals = $this->get('val');
		
		if (Phpfox::getService('link.process')->add($aVals, true))
		{
			$iId = Phpfox::getService('link.process')->getInsertId();
			
			$iAttachmentId = Phpfox::getService('attachment.process')->add(array(
					'category' => $aVals['category_id'],
					'link_id' => $iId
				)
			);			
			
			Phpfox::getBlock('link.display', array(
					'link_id' => $iId
				)
			);
			
			$this->call('var $oParent = $(\'#' . $aVals['attachment_obj_id'] . '\');');
			$this->call('$oParent.find(\'.js_attachment:first\').val($oParent.find(\'.js_attachment:first\').val() + \'' . $iAttachmentId . ',\'); $oParent.find(\'.js_attachment_list:first\').show(); $oParent.find(\'.js_attachment_list_holder:first\').prepend(\'<div class="attachment_row">' . $this->getContent() . '</div>\');');
			if (isset($aVals['attachment_inline']))
			{
				$this->call('$Core.clearInlineBox();');
			}
			else
			{
				$this->call('tb_remove();');
			}
		}
	}
	
	public function playVideo()
	{
		$aAttachment = Phpfox::getService('attachment')->getForDownload($this->get('attachment_id'));
		
		$sVideoPath = Phpfox::getParam('core.url_attachment') . $aAttachment['destination'];
		if (!empty($aAttachment['server_id']))
		{
			$sVideoPath = Phpfox::getLib('cdn')->getUrl($sVideoPath, $aAttachment['server_id']);	
		}		
		
		$sDivId = 'js_tmp_avideo_player_' . $aAttachment['attachment_id'];
		$this->html('#js_attachment_id_' . $this->get('attachment_id') . '', '<div id="' . $sDivId . '" style="width:480px; height:295px;"></div>');
		$this->call('$Core.player.load({id: \'' . $sDivId . '\', auto: true, type: \'video\', play: \'' . $sVideoPath . '\'}); $Core.player.play(\'' . $sDivId . '\', \'' . $sVideoPath . '\');');		
	}

    public function deleteAttachment()
    {
        $iItemId = $this->get('item_id');
        if (($iUserId = Phpfox::getService('attachment')->hasAccess($iItemId, 'delete_own_attachment', 'delete_user_attachment')) &&
            is_numeric($iUserId) && Phpfox::getService('attachment.process')->delete($iUserId, $iItemId)
        ) {
            $this->call("$('#js_attachment_id_" . $iItemId . "').remove();")
                ->call("$('.attachment_time_same_block').not(':has(.attachment-row)').remove()");
        }
    }

    /**
     * Update attachment view count
     */
    public function updateCounter()
    {
        Phpfox::getService('attachment.process')->updateCounter($this->get('item_id'));
    }
}