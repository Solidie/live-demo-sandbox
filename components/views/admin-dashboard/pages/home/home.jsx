import React, { useContext, useEffect, useState } from "react";

import {request} from 'crewhrm-materials/request.jsx';
import { ContextToast } from "crewhrm-materials/toast/toast.jsx";
import { LoadingIcon } from "crewhrm-materials/loading-icon/loading-icon.jsx";
import { __, data_pointer, isEmpty, purgeBasePath } from "crewhrm-materials/helpers.jsx";
import { TextField } from "crewhrm-materials/text-field/text-field.jsx";
import { confirm } from "crewhrm-materials/prompts.jsx";
import { FileUpload } from "crewhrm-materials/file-upload/file-upload.jsx";

import { HostInstance } from "./host-instance.jsx";

const status_class = 'text-align-center font-size-500 font-size-16'.classNames();

function generateStrongPassword(length = 12) {
    const uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const lowercase = 'abcdefghijklmnopqrstuvwxyz';
    const numbers = '0123456789';
    const specialChars = '!@#$%^&*()_+[]{}|.<>?';

    const allChars = uppercase + lowercase + numbers + specialChars;
    let password = '';

    // Ensure the password contains at least one character from each character set
    password += uppercase[Math.floor(Math.random() * uppercase.length)];
    password += lowercase[Math.floor(Math.random() * lowercase.length)];
    password += numbers[Math.floor(Math.random() * numbers.length)];
    password += specialChars[Math.floor(Math.random() * specialChars.length)];

    // Fill the remaining length with random characters from all sets
    for (let i = password.length; i < length; i++) {
        password += allChars[Math.floor(Math.random() * allChars.length)];
    }

    // Shuffle the password to ensure randomness
    password = password.split('').sort(() => 0.5 - Math.random()).join('');
    
    return password;
}

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
			modifier: purgeBasePath,
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
			modifier: purgeBasePath
		},
		admin_email: {
			label: __('Admin Email'),
			type: 'text',
			value: user.email,
		},
		admin_password: {
			label: __('Admin Password'),
			type: 'text',
			value: generateStrongPassword(20),
			modifier: purgeBasePath
		},
		plugins: {
			label: __('Plugins to install'),
			type: 'file',
			WpMedia: {mime_type: 'application/zip'},
			maxlength: 1000,
			removable: true,
			optional: true
		},
		theme: {
			label: __('Theme to install'),
			type: 'file',
			WpMedia: {mime_type: 'application/zip'},
			removable: true,
			optional: true,
		}
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
				request('multisiteSetupComplete', {}, resp=>{
					if ( !resp.success ) {
						ajaxToast(resp);
					} else {
						// Reload the page after multisite setup completed
						window.location.reload();
					}
				});
			}
		}

		return () => {
			delete window._slds_deployment_hook;
		}
	}, []);

	if ( configs.setup_complete ) {
		return <HostInstance configs={configs}/>
	}

	const has_empty = field_keys.filter(name=>!fields[name].optional && isEmpty(state.values[name])).length;
	const {port} = window.location;
	const {is_apache} = configs;

	return <div style={{margin: '50px auto', maxWidth: '800px'}}>
		<span className={'d-block margin-bottom-10 font-size-24 font-weight-600 margin-bottom-15'.classNames()}>
			{__('Sandbox Host')}
		</span>
		<div 
			style={{padding: '40px 20px'}} 
			className={'bg-color-white border-radius-8 border-1 b-color-text-10'.classNames()}
		>
			{
				state.step !== null ? <div className={'padding-vertical-15 margin-bottom-15'.classNames()}>
					<LoadingIcon show={true} center={true}/>
					<div className={'text-align-center color-error font-size-13 margin-top-10'.classNames()}>
						<i>{__('Do not close this tab until the process is complete.')}</i>
					</div>
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
								disabled={!is_apache || port}
							>
								{__('Set up now')}
							</button>
						</div> 
						:
						<div>
							{
								field_keys.map(name=>{
									const {type='text', label, modifier, removable, WpMedia, maxlength, switch_label} = fields[name];

									return <div key={name} className={'d-flex align-items-center column-gap-20 margin-bottom-30'.classNames()}>
										<label style={{width: '200px'}} className={'d-block font-size-16'.classNames()}>
											{label}
										</label>
										<div className={'flex-1'.classNames()}>
											{
												type!=='text' ? null :
												<TextField
													value={state.values[name]}
													onChange={v=>setVal(name, (modifier ? modifier(v) : v))}
												/>
											}
											
											{
												type!=='file' ? null :
												<FileUpload 
													WpMedia={WpMedia}
													maxlength={maxlength}
													onChange={v=>setVal(name, v)}
													value={state.values[name] || null}
													removable={removable}
												/>
											}

											{
												(name !== 'plugins' || (state.values[name]?.length || 0)<2) ? null :
												<span className={'d-block font-size-13 color-text-70 margin-top-5'.classNames()}>
													{__('Plugins will be installed according to the order you see')}
												</span>
											}
										</div>
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
				<div className={status_class}>
					{__('Downloading WordPress')}
				</div>
			}

			{
				state.step!==1 ? null :
				<div className={status_class}>
					{__('Installing WordPress')}
				</div>
			}

			{
				(state.step!==2 && state.step!==4) ? null :
				<div>
					<div className={status_class}>
						{state.step==2 ? __('Setting up Network') : __('Activating network wide themes and plugins')}
					</div>
					<div style={{margin: '0 auto', height: '2px', overflow: 'hidden', visibility: 'hidden'}}>
						<iframe style={{width: '800px', height: `${window.innerHeight - 150}px`}} src={state.iframe_url}></iframe>
					</div>
				</div>
			}

			{
				state.step !== 3 ? null :
				<div className={status_class}>
					{__('Deploying Network Configs')}
				</div>
			}
		</div>

		{
			! port ? null :
			<div className={'font-size-14 color-error text-align-center margin-top-25'.classNames()}>
				<i>{__('Sandbox is only supported for sites without a port in the URL.')}</i>
			</div>
		}

		{
			is_apache ? null :
			<div className={'font-size-14 color-error text-align-center margin-top-25'.classNames()}>
				<i>{__('Sandbox functionality is currently supported only on Apache servers.')}</i>
			</div>
		}
	</div>
}
