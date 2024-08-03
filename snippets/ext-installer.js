const slds_net_url                  = slds_variables.url_after_login;
const slds_net_theme_url            = slds_variables.themes_url;
const slds_ajax_url                 = slds_variables.ajax_url;
const slds_intent                   = slds_variables.intent;
const slds_exts                     = slds_variables.extensions;
const slds_redirect_to              = slds_variables.redirect_to;
const {slds_deployment_hook=()=>{}} = window.parent;

function slds_fetch_request(action, data, callback) {
	
	data = {...data, action};
	const payload = new URLSearchParams();
	for ( k in data) {
		payload.append(k, data[k]);
	}
	
	fetch(slds_ajax_url, {
		method: 'POST',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded'
		},
		body: payload
	})
	.then(response => {
		if (!response.ok) {
			throw new Error('Network response was not ok ' + response.statusText);
		}
		return response.json();
	})
	.then(data => {
		callback(data);
	})
	.catch(error => {
		alert('Request error');
	});
}

window.addEventListener('load', function(){

	switch(slds_intent) {

		case 'login' :
			const data = new URLSearchParams();
			data.append('action', 'slds_login_to_admin');

			slds_fetch_request('slds_login_to_admin', {}, resp=>{
				if ( ! resp?.success ) {
					alert('Multisite admin login failed');
					return;
				}
				window.location.assign(slds_net_url);
			});
			
			break;
		
		case 'setup' :
			const button = document.getElementById('submit');
			if ( button ) {
				button.click();
			} else {
				slds_deployment_hook(3);
			}
			break;

		case 'plugins' :
		case 'themes'  :
			
			let       found  = false;
			const is_plugins = slds_intent === 'plugins';

			// Loop through plugins/themes to avtivate network wide
			for ( let i=0; i<slds_exts.length; i++ ) {

				const {
					dir_name, 
					type, 
					network=false
				} = slds_exts[i];

				const network_wide = !is_plugins || network;
				const selector     = is_plugins ? `[data-plugin^="${dir_name}/"] span.activate a` : `[data-slug="${dir_name}"] span.enable a`;
				const anchor       = window.jQuery(selector);

				if ( network_wide && anchor.length ) {
					found = true;
					window.location.assign(anchor.attr('href'));
				}
			}

			// Open themes page now if no more plugins to activate network wide.
			if ( ! found && is_plugins ) {
				window.location.assign(slds_net_theme_url);
				return;
			}

			if ( ! found ) {
				slds_fetch_request('slds_complete_setup', {}, resp=>{
					if ( ! resp?.success ) {
						alert('Could not mark as setup complete');
						return;
					}

					slds_deployment_hook(5);
				});
			}
			break;

		case 'redirect' :
			window.location.assign(slds_redirect_to);
			break;
	}
});