/* ETATS */
INSERT INTO `etat`(`libelle`) VALUES ("Ouvert");
INSERT INTO `etat`(`libelle`) VALUES ("En cours");
INSERT INTO `etat`(`libelle`) VALUES ("Annulee");
INSERT INTO `etat`(`libelle`) VALUES ("Terminee");
INSERT INTO `etat`(`libelle`) VALUES ("En creation");
INSERT INTO `etat`(`libelle`) VALUES ("Ferme");

/* SITES */
INSERT INTO `site`(`nom`) VALUES ("ENI Saint Herblain");
INSERT INTO `site`(`nom`) VALUES ("ENI Niort");

/* VILLES */
INSERT INTO `ville`(`nom`, `code_postal`) VALUES ("Nantes","44000");
INSERT INTO `ville`(`nom`, `code_postal`) VALUES ("Niort","79000");
INSERT INTO `ville`(`nom`, `code_postal`) VALUES ("La Roche sur Yon","79000");

/* LIEU */
INSERT INTO `lieu`(`ville_id`, `nom`, `rue`, `latitude`, `longitude`) VALUES (1,"Zénith Nantes Métropole","ZAC d'Ar Mor, Bd du Zénith", 47.228709, -1.628865);
INSERT INTO `lieu`(`ville_id`, `nom`, `rue`, `latitude`, `longitude`) VALUES (1,"Jardin des Plantes","Rue Stanislas Baudry", 47.218178, -1.541909);
INSERT INTO `lieu`(`ville_id`, `nom`, `rue`, `latitude`, `longitude`) VALUES (2,"Cinéma CGR Niort","Place de la Brèche", null, null);
INSERT INTO `lieu`(`ville_id`, `nom`, `rue`, `latitude`, `longitude`) VALUES (2,"Musée du Donjon","Rue du Guesclin", null, null);
INSERT INTO `lieu`(`ville_id`, `nom`, `rue`, `latitude`, `longitude`) VALUES (3,"Zénith Nantes Métropole","ZAC d'Ar Mor, Bd du Zénith", null, null);

/* PARTICIPANT */
INSERT INTO `participant`(`email`, `roles`, `password`, `pseudo`, `nom`, `prenom`, `telephone`, `site_id`, `actif`) 
    VALUES ("pierre.ladmin",'["ROLE_ADMIN"]',"$2y$13$z60xN3leUE4JB6NGUjXMQ.qpOPX0eOnwPC66z46f4kyK6bnMNjnFC","admin","Ladmin","Pierre","0102030405",1,1);
INSERT INTO `participant`(`email`, `roles`, `password`, `pseudo`, `nom`, `prenom`, `telephone`, `site_id`, `actif`) 
    VALUES ("bernard.lhermite",'[]',"$2y$13$z60xN3leUE4JB6NGUjXMQ.qpOPX0eOnwPC66z46f4kyK6bnMNjnFC","bernard","LHERMITE","Bernard",null,1,1);
INSERT INTO `participant`(`email`, `roles`, `password`, `pseudo`, `nom`, `prenom`, `telephone`, `site_id`, `actif`) 
    VALUES ("alex.terieur",'[]',"$2y$13$z60xN3leUE4JB6NGUjXMQ.qpOPX0eOnwPC66z46f4kyK6bnMNjnFC","alex","Terieur","Alex","0203040506",2,1);

/* SORTIE */
INSERT INTO `sortie`(`lieu_id`, `organisateur_id`, `etat_id`, `nom`, `date_debut`, `duree`, `date_cloture`, `nb_inscriptions_max`, `description_infos`, `url_photo`, `motif_annulation`, `site_id`)
    VALUES (1,2,1,"Conférence cybersécurité","2021-12-01",1,"2021-11-25",5,"Conférence sur la cybersécurité",null,null,1);
INSERT INTO `sortie`(`lieu_id`, `organisateur_id`, `etat_id`, `nom`, `date_debut`, `duree`, `date_cloture`, `nb_inscriptions_max`, `description_infos`, `url_photo`, `motif_annulation`, `site_id`)
    VALUES (2,2,3,"Green Coding","2021-11-20",1,"2021-11-15",10,null,null,"Pluie",1);
INSERT INTO `sortie`(`lieu_id`, `organisateur_id`, `etat_id`, `nom`, `date_debut`, `duree`, `date_cloture`, `nb_inscriptions_max`, `description_infos`, `url_photo`, `motif_annulation`, `site_id`)
    VALUES (3,3,1,"Film sur l'informatique","2021-12-15",1,"2021-12-10",7,null,null,null,2);
INSERT INTO `sortie`(`lieu_id`, `organisateur_id`, `etat_id`, `nom`, `date_debut`, `duree`, `date_cloture`, `nb_inscriptions_max`, `description_infos`, `url_photo`, `motif_annulation`, `site_id`)
    VALUES (1,3,4,"Conférence base de données","2021-10-15",1,"2021-10-01",10,null,null,null,1);

/* INSCRIPTION */
INSERT INTO `participant_sortie`(`participant_id`, `sortie_id`) VALUES (3,3);
INSERT INTO `participant_sortie`(`participant_id`, `sortie_id`) VALUES (3,4);
INSERT INTO `participant_sortie`(`participant_id`, `sortie_id`) VALUES (2,4);
INSERT INTO `participant_sortie`(`participant_id`, `sortie_id`) VALUES (1,4);

/* EVENTS */
CREATE DEFINER=`root`@`localhost` EVENT `archiver_sorties` ON SCHEDULE EVERY 2 HOUR STARTS '2021-11-17 09:50:31' ENDS '2021-11-21 09:50:31' ON COMPLETION PRESERVE ENABLE DO INSERT INTO sortie_archivee (id, lieu_id, organisateur_id, etat_id, nom, date_debut, duree, date_cloture, nb_inscriptions_max, description_infos, url_photo, motif_annulation, site_id) SELECT * FROM sortie WHERE date_debut <= NOW() - INTERVAL 1 MONTH;
CREATE DEFINER=`root`@`localhost` EVENT `delete_sorties` ON SCHEDULE EVERY 2 HOUR STARTS '2021-11-17 09:52:41' ENDS '2021-11-21 09:52:41' ON COMPLETION PRESERVE ENABLE DO DELETE sortie.* FROM sortie inner join sortie_archivee on sortie.id = sortie_archivee.id;

/* php bin/console security:encode-password */