// ----------------------------------------------------------------------
// Original Author of file: Nicolas Mercier
// Purpose of file: Affichage dynamique des onglets
// ----------------------------------------------------------------------

// permet de changer d'onglet et masque le contenu des autres
function ChangeOnglet(active, nb_elem, tab_prefix, contenu_prefix) 
{   
    for (var i=1; i < nb_elem + 1; i++)
    {
        document.getElementById(contenu_prefix + i).style.display = 'none';
		document.getElementById('li'+i).className = '';
    }  
    
    document.getElementById(contenu_prefix+active).style.display = 'block';
    document.getElementById('li'+active).className = 'x-tab-strip-active';
}  // ChangeOnglet()


