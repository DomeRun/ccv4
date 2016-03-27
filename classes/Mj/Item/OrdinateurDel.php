<?php
/** Gestion de l'interface pour les items
*
* @package Mj
*/
class Mj_Item_OrdinateurDel
{
	public static function generatePage(&$tpl, &$session, &$account, &$mj)
	{
		if (!isset($_POST['db_id']))
			return fctErrorMSG("Aucun item sélectionné.");
		
		if($_POST['db_id'] < 10)
			return fctErrorMSG("Item système, suppression interdite.");

		$query = 'DELETE FROM `' . DB_PREFIX . 'item_db`'
					. ' WHERE `db_id` = :db_id'
					. ' LIMIT 1;';
		$prep = $db->prepare($query);
		$prep->bindValue(':db_id', $_POST['db_id'], PDO::PARAM_INT);
		$prep->executePlus($db, __FILE__,__LINE__);
		$prep->closeCursor();
		$prep = NULL;

		//Suppression de l'item en inventaire
		$query = 'DELETE FROM `' . DB_PREFIX . 'item_inv`'
					. ' WHERE `inv_dbid` = :db_id';
		$prep = $db->prepare($query);
		$prep->bindValue(':db_id', $_POST['db_id'], PDO::PARAM_INT);
		$prep->executePlus($db, __FILE__,__LINE__);
		$prep->closeCursor();
		$prep = NULL;
		
		//Suppression des actions actuelles
		$query = 'DELETE FROM `' . DB_PREFIX . 'item_menu`'
					. ' WHERE `item_dbid` = :db_id';
		$prep = $db->prepare($query);
		$prep->bindValue(':db_id', $_POST['db_id'], PDO::PARAM_INT);
		$prep->executePlus($db, __FILE__,__LINE__);
		$prep->closeCursor();
		$prep = NULL;
		
		die("<script>location.href='?mj=Item_Ordinateur';</script>");
	}
}
