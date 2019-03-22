<?php

// Copyright (C) 2010-2017 Combodo SARL
//
//   This file is part of iTop.
//
//   iTop is free software; you can redistribute it and/or modify
//   it under the terms of the GNU Affero General Public License as published by
//   the Free Software Foundation, either version 3 of the License, or
//   (at your option) any later version.
//
//   iTop is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU Affero General Public License for more details.
//
//   You should have received a copy of the GNU Affero General Public License
//   along with iTop. If not, see <http://www.gnu.org/licenses/>


/**
 * Archive/Unarchive a single/set of object(s)
 *
 * @copyright   Copyright (C) 2010-2017 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

require_once('../approot.inc.php');
require_once(APPROOT.'/application/application.inc.php');
require_once(APPROOT.'/application/itopwebpage.class.inc.php');

require_once(APPROOT.'/application/startup.inc.php');

require_once(APPROOT.'/application/loginwebpage.class.inc.php');
LoginWebPage::DoLogin(true); // Check user rights and prompt if needed (must be admin)

$sOperation = utils::ReadParam('operation', '');
$oAppContext = new ApplicationContext();

$oP = new iTopWebPage(Dict::S("Archive:Title:ActionPage", 'Archiving...'));
$oP->DisableBreadCrumb();

const DIV_MESSAGE_OK = '<div class="header_message message_ok">';

try
{
	switch ($sOperation)
	{
		case 'archive_item':
			$sClass = utils::ReadParam('class');
			$iId = utils::ReadParam('id');
			$oObject = MetaModel::GetObject($sClass, $iId, true, true);

			$oP->add("<h1>".Dict::S('Archive:Title:Archiving')."</h1>");
			$oObject->DBArchive();
			cmdbAbstractObject::SetSessionMessage(get_class($oObject), $oObject->GetKey(), 'just-archived',
				Dict::S('Msg:ArchivedSuccess'), 'ok', 0, true /* must not exist */);

			$oP->add(DIV_MESSAGE_OK.Dict::Format("Archive:Message:Archiving", $oObject->GetHyperlink())."</div>");

			$sUrl = ApplicationContext::MakeObjectUrl($sClass, $iId);
			$oP->p(Dict::Format("Archive:Message:Redirect", $sUrl));
			$oP->add_header("Location: $sUrl");
			break;

		case 'unarchive_item':
			$sClass = utils::ReadParam('class');
			$iId = utils::ReadParam('id');
			$oObject = MetaModel::GetObjectWithArchive($sClass, $iId, true, true);

			$oP->add("<h1>".Dict::S('Archive:Title:UnArchiving')."</h1>");
			$oObject->DBUnarchive();
			cmdbAbstractObject::SetSessionMessage(get_class($oObject), $oObject->GetKey(), 'just-unarchived',
				Dict::S('Msg:UnarchivedSuccess'), 'ok', 0, true /* must not exist */);

			$oP->add(DIV_MESSAGE_OK.Dict::Format("Archive:Message:UnArchiving", $oObject->GetHyperlink())."</div>");

			$sUrl = ApplicationContext::MakeObjectUrl($sClass, $iId);
			$oP->p(Dict::Format("Archive:Message:Redirect", $sUrl));
			$oP->add_header("Location: $sUrl");
			break;

		case 'confirm_archive_list':
			$sClass = utils::ReadParam('class', '', false, 'raw_data');
			if (!ArchiveUtils::CanArchive($sClass))
			{
				throw new SecurityException("Not allowed to archive objects");
			}
			$sClassName = MetaModel::GetName($sClass);

			$sOQL = utils::ReadParam('scope', '', false, 'raw_data');
			$oSet = new CMDBObjectSet(DBObjectSearch::FromOQL($sOQL));
			$oSet->SetShowObsoleteData(utils::ShowObsoleteData()); // Obsolescence filter
			$iCount = $oSet->Count();

			$oP->add("<h1>".Dict::Format('Archive:Confirm:ArchivingList', $iCount, $sClassName)."</h1>");

			$aExtraParams = array(
				'menu' => false,
				'table_id' => 'archiveListConfirmation',
			);
			$oBlock = new DisplayBlock($oSet->GetFilter(), 'list', false, $aExtraParams);
			$oBlock->Display($oP, 0);

			$oP->add("<form method=\"post\">\n");
			$oP->add(ArchiveUtils::GetActionPageHtmlHiddenInputsForMassUpdate($sClass, 'archive_list', $sOQL));
			$oP->add("<input type=\"button\" onclick=\"window.history.back();\" value=\"".Dict::S('UI:Button:Back')."\">\n");
			$oP->add("<input type=\"submit\" name=\"\" value=\"".Dict::S('UI:Button:Archive')."\">\n");
			$oP->add("</form>\n");
			break;

		case 'confirm_unarchive_list':
			$sClass = utils::ReadParam('class', '', false, 'raw_data');
			if (!ArchiveUtils::CanArchive($sClass))
			{
				throw new SecurityException("Not allowed to unarchive objects");
			}
			$sClassName = MetaModel::GetName($sClass);

			$sOQL = utils::ReadParam('scope', '', false, 'raw_data');
			$oSet = new CMDBObjectSet(DBObjectSearch::FromOQL($sOQL));
			$oSet->SetShowObsoleteData(utils::ShowObsoleteData()); // Obsolescence filter
			$iCount = $oSet->Count();

			$oP->add("<h1>".Dict::Format('Archive:Confirm:UnArchivingList', $iCount, $sClassName)."</h1>");

			$aExtraParams = array(
				'menu' => false,
				'table_id' => 'archiveListConfirmation',
			);
			$oBlock = new DisplayBlock($oSet->GetFilter(), 'list', false, $aExtraParams);
			$oBlock->Display($oP, 0);

			$oP->add("<form method=\"post\">\n");
			$oP->add(ArchiveUtils::GetActionPageHtmlHiddenInputsForMassUpdate($sClass, 'unarchive_list', $sOQL));
			$oP->add("<input type=\"button\" onclick=\"window.history.back();\" value=\"".Dict::S('UI:Button:Back')."\">\n");
			$oP->add("<input type=\"submit\" name=\"\" value=\"".Dict::S('UI:Button:UnArchive')."\">\n");
			$oP->add("</form>\n");
			break;

		case 'archive_list':
			$sScope = utils::ReadParam('scope', null, false, 'raw_data');
			$oSearch = DBSearch::FromOQL($sScope);
			$oSet = new DBObjectSet($oSearch);
			$oSet->SetShowObsoleteData(utils::ShowObsoleteData()); // Obsolescence filter
			$iObjectsCount = $oSet->Count();

			$oP->add("<h1>".Dict::S('Archive:Title:ArchivingList')."</h1>");
			$fStarted = microtime(true);
			$oSet->GetFilter()->DBBulkWriteArchiveFlag(true);
			$fElapsed = microtime(true) - $fStarted;
			$sProcessTime = (round($fElapsed, 3))."s";

			$oP->add(DIV_MESSAGE_OK.Dict::Format('Archive:Message:ArchivingList', $iObjectsCount).'</div>');
			$oP->p(Dict::Format('Archive:Message:ListTechnical', $sProcessTime));
			break;

		case 'unarchive_list':
			$sScope = utils::ReadParam('scope', null, false, 'raw_data');
			$oSearch = DBSearch::FromOQL($sScope);
			$oSet = new DBObjectSet($oSearch);
			$oSet->SetShowObsoleteData(utils::ShowObsoleteData()); // Obsolescence filter
			$iObjectsCount = $oSet->Count();

			$oP->add("<h1>".Dict::S('Archive:Title:UnArchivingList')."</h1>");
			$fStarted = microtime(true);
			$oSet->GetFilter()->DBBulkWriteArchiveFlag(false);
			$fElapsed = microtime(true) - $fStarted;
			$sProcessTime = (round($fElapsed, 3))."s";

			$oP->add(DIV_MESSAGE_OK.Dict::Format('Archive:Message:UnArchivingList', $iObjectsCount).'</div>');
			$oP->p(Dict::Format('Archive:Message:ListTechnical', $sProcessTime));
			break;

		default:
			throw new CoreUnexpectedValue('Not a supported operation : '.$sOperation);
			break;
	}
} catch (Exception $e)
{
	$sExceptionMessage = $e->getMessage();
	$oP->add(<<<EOT
<div class="header_message message_error">An error occured !<br>
$sExceptionMessage
EOT
	);
}

$oP->output();
