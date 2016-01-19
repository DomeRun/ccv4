<?php
/** Gestion d'un sac a dos
*
* @package Member_Action
*/
class Member_Action_Item_SacPersoVersSac
{
	public static function generatePage(&$tpl, &$session, &$account, &$perso)
	{
		$dbMgr = DbManager::getInstance(); //Instancier le gestionnaire
		$db = $dbMgr->getConn('game'); //Demander la connexion existante	//BUT: Démarrer un template propre à cette page
		
		$errorUrl = '?popup=1&amp;m=Action_Item_Sac';
		
		
		//Vérifier l'état du perso
		if(!$perso->isConscient())
			return fctErrorMSG('Votre n\'êtes pas en état d\'effectuer cette action.', $errorUrl);
		
		
		//Si aucun sac n'a été choisi
		if(!isset($_POST['sacId']) || !is_numeric($_POST['sacId']))
			return fctErrorMSG('Aucun sac n\'a été sélectionné.', $errorUrl);
		
		
		//Déclaration des variables pour cette action
		$totalPr = 0;
		$totalPa = 0;
		$paCostPerItem = 0;
		$totalItems = 0;
		
		//Vérifier si le personnage possède le sac dans son inventaire
		//Calculer les totaux des PR et PA du transfert
		$i=0;
		$sacFound=false;
		while( $item = $perso->getInventaire($i++))
		{
		
			//Vérifier si l'item est le sac dans lequel on veux transférer des items
			if($item instanceof Member_ItemSac && $item->getInvId() == $_POST['sacId'])
				$sacFound=$item;
			
			//Vérifier si l'item fait parti de ceux que l'on désire transférer
			if(isset($_POST[$item->getInvId() . '_qte']))
			{
				$qte = $_POST[$item->getInvId() . '_qte'];
				if(is_numeric($qte) && $qte > 0)
				{
					//Valider si la quantité à tranférer ne dépasse pas la quantité possédée
					if ($item->getQte() < $qte)
						return fctErrorMSG('Vous ne pouvez pas transférer plus que vous possèdez.', $errorUrl);
		
					//Valider si l'item est non-équipée
					if($item->isEquip())
						return fctErrorMSG('Vous ne pouvez pas transférer un item équipé.', $errorUrl);
				
					//Ajuster les totaux
					$totalPr += $item->getPr() * $qte;
					$totalPa += $paCostPerItem;
					$totalItems++;
				}
			}
			
		}
		
		//Valider s'il y a des items à transférer
		if($totalItems==0)
			return fctErrorMSG('Vous n\'avez rien transféré.', $errorUrl);
		
		//Valider si le sac nous appartient
		if($sacFound===false)
			return fctErrorMSG('Ce sac ne vous appartiend pas.', $errorUrl);
		
		//Valider si le perso possède assez de PA pour effectuer le transfert
		if($perso->getPa() <= $totalPa)
			return fctErrorMSG('Vous n\'avez pas assez de PA.', $errorUrl);
		
		//Valider si le sac à la capacité d'accueillir les items à transférer
		$sacFound->refreshInventaire(); //Charger l'inventaire afin de calculer les PR
		
		
		if(($sacFound->getCapaciteMax() - $sacFound->getInvPr()) < $totalPr)
			return fctErrorMSG('Ce sac n\'a pas suffisamment de PR de libre pour accepter cette transaction.', $errorUrl);
		
		
		
		//Effectuer les transferts
		$i=0;
		$itemsList = '';
		while( $item = $perso->getInventaire($i++))
		{
			//Vérifier si l'item fait parti de ceux que l'on désire transférer
			if(isset($_POST[$item->getInvId() . '_qte']))
			{
				$qte = $_POST[$item->getInvId() . '_qte'];
				if(is_numeric($qte) && $qte > 0)
				{
					//Préparer le message de l'HE
					$itemsList .= (empty($itemsList) ? '' : ', ') . '[i]' . $item->getNom() . '[/i] x' . $qte;
					
					//S'assurer qu'on ne transfère pas le sac dans lui-même (Erreur de dernier recours)
					if($item->getInvId() == $sacFound->getInvId())
						return fctErrorMSG('Mettre le sac dans le sac, c\'est comme un serpent qui mange sa queue...', $errorUrl);
					
					//Déplacer l'item vers l'inventaire du sac
					$item->transfererVersItem($sacFound, $qte);
				}
			}
		}
		
		
		//Retirer les PA et actualiser l'inventaire
		$perso->refreshInventaire();
		$perso->changePa('-', $totalPa);
		$perso->setPa();
		
		
		//Ajouter le message dans le HE
		$msg = "Objet(s) transféré(s):\n" . $itemsList . "\n\n";
		$msg.= "Dans le sac: [i]" . $sacFound->getNom() . "[/i]";
		Member_He::add($perso->getId(), 'System', 'sac', $msg );
		
		
		
		$tpl->set('PAGE', 'Action_Item_Sac&sacId=' . $sacFound->getInvId());
		return $tpl->fetch($account->getSkinRemotePhysicalPath() . 'html/Member/action_redirect.htm',__FILE__,__LINE__);
	}
}

