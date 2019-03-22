<?php

class ManualArchivePlugin implements iPopupMenuExtension
{
	/**
	 * Get the list of items to be added to a menu.
	 *
	 * This method is called by the framework for each menu.
	 * The items will be inserted in the menu in the order of the returned array.
	 *
	 * @param int $iMenuId The identifier of the type of menu, as listed by the constants MENU_xxx
	 * @param DBObject $param Depends on $iMenuId, see the constants defined above
	 *
	 * @return URLPopupMenuItem[] An array of ApplicationPopupMenuItem or an empty array if no action is to be added to the menu
	 */
	public static function EnumItems($iMenuId, $param)
	{
		$aRet = array();
		if ($iMenuId == iPopupMenuExtension::MENU_OBJDETAILS_ACTIONS)
		{
			$oObject = $param;
			$sClass = get_class($oObject);
			$iId = intval($oObject->GetKey());

			if (ArchiveUtils::CanArchive($sClass))
			{
				if ($oObject->IsArchived())
				{
					// Menu to Unarchive : when archive mode is disabled the menu are not shown so this can't be accessed
					$sOperation = "unarchive_item";
					$sArchiveUrl = ArchiveUtils::GetActionPageUrlForSingleObject($sClass, $sOperation, $iId);;
					$aRet[] = new URLPopupMenuItem('unarchive_item', Dict::S('Action:UnarchiveItem'), $sArchiveUrl);
				}
				else
				{
					// Menu to Archive
					$sOperation = "archive_item";
					$sArchiveUrl = ArchiveUtils::GetActionPageUrlForSingleObject($sClass, $sOperation, $iId);;
					$aRet[] = new URLPopupMenuItem('archive_item', Dict::S('Action:ArchiveItem'), $sArchiveUrl);
				}
			}
		}
		elseif ($iMenuId == iPopupMenuExtension::MENU_OBJLIST_ACTIONS)
		{
			$oSet = $param;
			$sClass = $oSet->GetFilter()->GetClass();

			if (ArchiveUtils::CanArchive($sClass))
			{
				$sScope = $oSet->GetFilter()->ToOQL(true);

				// Menu to UnArchive
				if (utils::IsArchiveMode())
				{
					$sOperation = "confirm_unarchive_list";
					$sArchiveUrl = ArchiveUtils::GetActionPageUrlForMassUpdate($sClass, $sOperation, $sScope);
					$aRet[] = new URLPopupMenuItem('unarchive_list', Dict::S('Action:UnarchiveList'), $sArchiveUrl);
				}

				// Menu to Archive
				$sOperation = "confirm_archive_list";
				$sArchiveUrl = ArchiveUtils::GetActionPageUrlForMassUpdate($sClass, $sOperation, $sScope);
				$aRet[] = new URLPopupMenuItem('archive_list', Dict::S('Action:ArchiveList'), $sArchiveUrl);
			}
		}
		return $aRet;
	}
}

class ArchiveUtils
{
	/**
	 * @param string $sClass
	 *
	 * @return bool true if the archive/unarchive functionnality can be used
	 */
	public static function CanArchive($sClass)
	{
		return UserRights::IsAdministrator() && MetaModel::IsArchivable($sClass);
	}

	/**
	 * @param string $sClass
	 * @param string $sOperation
	 * @param int $iId
	 *
	 * @return string
	 */
	public static function GetActionPageUrlForSingleObject($sClass, $sOperation, $iId)
	{
		return self::GetActionPageUrl($sClass, $sOperation, $iId, null);
	}

	/**
	 * @param string $sClass
	 * @param string $sOperation
	 * @param string $sScope
	 *
	 * @return string
	 */
	public static function GetActionPageUrlForMassUpdate($sClass, $sOperation, $sScope)
	{
		return self::GetActionPageUrl($sClass, $sOperation, null, $sScope);
	}

	/**
	 * @param string $sClass
	 * @param string $sOperation
	 * @param int $iId for single update
	 * @param string $sScope for mass update, query to retrieve objects to modify
	 *
	 * @return string
	 */
	private static function GetActionPageUrl($sClass, $sOperation, $iId, $sScope)
	{
		$sModuleName = basename(__DIR__);

		$aActionArgs = self::GetActionPageArgs($sClass, $sOperation, $iId, $sScope);

		$sActionPageUrl = utils::GetAbsoluteUrlModulePage($sModuleName, "actions.php", $aActionArgs);

		return $sActionPageUrl;
	}

	/**
	 * @param string $sClass
	 * @param string $sOperation
	 * @param int $iId for single update
	 * @param string $sScope for mass update, query to retrieve objects to modify
	 *
	 * @return string[]
	 */
	private static function GetActionPageArgs($sClass, $sOperation, $iId, $sScope)
	{
		$oAppContext = new ApplicationContext();
		$aUiPageArgs = $oAppContext->GetAsHash();

		$aUiPageArgs['operation'] = $sOperation;
		$aUiPageArgs['class'] = $sClass;

		if (is_int($iId))
		{
			$aUiPageArgs['id'] = $iId;
		}
		if (is_string($sScope))
		{
			$aUiPageArgs['scope'] = $sScope;
		}

		return $aUiPageArgs;
	}

	/**
	 * @param string $sClass
	 * @param string $sOperation
	 * @param string $sScope for mass update, query to retrieve objects to modify
	 *
	 * @return string
	 */
	public static function GetActionPageHtmlHiddenInputsForMassUpdate($sClass, $sOperation, $sScope)
	{
		$sRet = '';

		$aActionPageArgs = ArchiveUtils::GetActionPageArgs($sClass, $sOperation, null, $sScope);
		foreach($aActionPageArgs as $sName => $sValue)
		{
			$sRet .= '<input type="hidden" name="'.$sName.'" value="'.$sValue.'">';
		}

		return $sRet;
	}
}
