import React, { useContext, useEffect, useState } from "react";

import {request} from 'crewhrm-materials/request.jsx';
import { ContextToast } from "crewhrm-materials/toast/toast.jsx";
import { LoadingIcon } from "crewhrm-materials/loading-icon/loading-icon.jsx";
import { __, data_pointer, isEmpty } from "crewhrm-materials/helpers.jsx";
import { TextField } from "crewhrm-materials/text-field/text-field.jsx";
import { confirm } from "crewhrm-materials/prompts.jsx";

export function HomeBackend(props) {

	const {configs={}} = props;
	const {ajaxToast} = useContext(ContextToast);
	const {user={}} = window[data_pointer];

	const fields = {
		db_name: {
			label: __('Database Name'),
			type: 'text',
			value: configs.db_name,
		},
		db_user: {
			label: __('Database Username'),
			type: 'text',
			value: configs.db_user,
		},
		db_password: {
			label: __('Database Password'),
			type: 'text',
			value: configs.db_password,
		},
		db_host: {
			label: __('Database Host'),
			type: 'text',
			value: configs.db_host,
		},
		table_prefix: {
			label: __('Table Prefix'),
			type: 'text',
			value: configs.table_prefix,
		},
		directory_name: {
			label: __('Installation Directory Name'),
			type: 'text',
			value: 'demo',
		},
		site_title: {
			label: __('Site Title'),
			type: 'text',
			value: 'Demo Multisite',
		},
		admin_username: {
			label: __('Admin Username'),
			type: 'text',
			value: user.username,
		},
		admin_email: {
			label: __('Admin Email'),
			type: 'text',
			value: user.email,
		},
		admin_password: {
			label: __('Admin Password'),
			type: 'text',
			value: 'ZJ3VA^jSxmq9&%k!',
		},
	}
		
	const field_keys = Object.keys(fields);
	const default_values = {};
	field_keys.forEach(name=>{
		default_values[name] = configs[name] || fields[name].value;
	});

	const [state, setState] = useState({
		step: null,
		iframe_url: null,
		show_form: false,
		values: default_values
	});

	const setVal=(name, value)=>{
		setState({
			...state,
			values: {
				...state.values,
				[name]: value
			}
		});
	}

	const downloadWP=()=>{
		confirm(
			__('Sure to Start setup?'),
			__('You must keep the tab open until completion. Otherwise you\'ll have to reset setup.'),
			()=>{
				setState({
					...state,
					step: 0
				});
				
				request('downloadWP', {}, resp=>{

					if ( resp.success ) {
						init();
						return;
					}
				
					setState({
						...state,
						step: null
					});

					ajaxToast(resp);
				});
			}
		);
	}

	const init=(params={})=>{
		
		setState({
			...state,
			step: 1
		});

		request('initBaseInstance', {configs: {...state.values, ...params}}, resp=>{

			setState({
				...state,
				step: resp.success ? 2 : null,
				iframe_url: resp.data?.iframe_url || null
			});

			if ( ! resp.success ) {
				if ( resp.data?.duplicate ) {
					confirm(
						__('The directory exists already'),
						__('Would you like to override it? It will delete all the sandbox if there is any.'),
						()=>{
							init({override: true});
						}
					);
				} else {
					ajaxToast(resp);
				}
			}
		});
	}

	const deployConfigs=()=>{
		
		setState({
			...state,
			step: 3
		});

		request('deployNetworkConfigs', {}, resp=>{

			setState({
				...state,
				step: resp.success ? 4 : null,
				iframe_url: resp.data.iframe_url
			});

			if ( ! resp.success ) {
				ajaxToast(resp);
			}
		});
	}

	useEffect(()=>{
		window._slds_deployment_hook = (step)=>{

			if ( step === 3 ) {
				deployConfigs();

			} else if ( step === 5 ) {
				// Reload the page after multisite setup completed
				window.location.reload();
			}
		}

		return () => {
			delete window._slds_deployment_hook;
		}
	}, []);

	if ( configs.setup_complete ) {
		return <div>
			All Done
		</div>
	}

	const has_empty = field_keys.filter(name=>isEmpty(state.values[name])).length;

	return <div style={{margin: '50px auto', maxWidth: '600px'}}>
		<span className={'d-block margin-bottom-10 font-size-24 font-weight-600 margin-bottom-15'.classNames()}>
			{__('Sandbox Host')}
		</span>
		<div 
			style={{padding: '40px 20px'}} 
			className={'bg-color-white border-radius-8 border-1 b-color-text-10'.classNames()}
		>

			{
				state.step !== null ? <div className={'padding-vertical-15'.classNames()}>
					<LoadingIcon show={true} center={true}/>
				</div> 
				: 
				<div>
					{
						!state.show_form ? <div className={'text-align-center'.classNames()}>
							<span className={'d-block margin-bottom-15 font-size-16 font-weight-400 color-text-80'.classNames()}>
								{__('No environment is setup yet to create sandbox under.')}
							</span>
							<button 
								className={'button button-primary'.classNames()} 
								onClick={()=>setState({...state, show_form: true})}
							>
								{__('Set up now')}
							</button>
						</div> 
						:
						<div>
							{
								field_keys.map(name=>{
									const {type='text', label} = fields[name];

									return <div key={name} className={'margin-bottom-20'.classNames()}>
										<label className={'d-block margin-bottom-5 font-size-16'.classNames()}>
											{label}
										</label>
										{
											type!=='text' ? null :
											<TextField
												value={state.values[name]}
												onChange={v=>setVal(name, v)}
											/>
										}
									</div>
								})
							}

							<div className={'d-flex align-items-center column-gap-8'.classNames()}>
								<div className={'flex-1'.classNames()}>
									{
										!has_empty ? null :
										<span className={'color-error'.classNames()}>
											All fields are required
										</span>
									}
								</div>
								<button 
									className={'button button-outlined'.classNames()}
									onClick={()=>setState({...state, show_form: false})}
								>
									{__('Cancel')}
								</button>
								<button 
									className={'button button-primary'.classNames()}
									onClick={downloadWP}
									disabled={has_empty}
								>
									{__('Run Setup')}
								</button>
							</div>
						</div>
					}
				</div>
			}

			{
				state.step !== 0 ? null :
				<div>
					Downloading WordPress
				</div>
			}

			{
				state.step!==1 ? null :
				<div>
					Installing WordPress
				</div>
			}

			{
				(state.step!==2 && state.step!==4) ? null :
				<div>
					{state.step==2 ? __('Setting up Network') : __('Activating extensions')}
					<div style={{margin: '0 auto'}}>
						<iframe style={{width: '800px', height: `${window.innerHeight - 150}px`}} src={state.iframe_url}></iframe>
					</div>
				</div>
			}

			{
				state.step !== 3 ? null :
				<div>
					Deploying Network Configs
				</div>
			}
		</div>
	</div>
}
