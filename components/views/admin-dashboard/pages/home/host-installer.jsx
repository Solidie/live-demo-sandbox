import React, { useContext, useEffect, useState } from "react";
import { Link, useNavigate } from 'react-router-dom';

import {request} from 'solidie-materials/request.jsx';
import { ContextToast } from "solidie-materials/toast/toast.jsx";
import { LoadingIcon } from "solidie-materials/loading-icon/loading-icon.jsx";
import { __, data_pointer, getBack, getRandomString, isEmpty, purgeBasePath } from "solidie-materials/helpers.jsx";
import { TextField } from "solidie-materials/text-field/text-field.jsx";
import { confirm } from "solidie-materials/prompts.jsx";
import { FileUpload } from "solidie-materials/file-upload/file-upload.jsx";
import { applyFilters } from 'solidie-materials/hooks.jsx';
import { ToggleSwitch } from "solidie-materials/toggle-switch/ToggleSwitch.jsx";
import { section_class } from "./host-instance";

const status_class = 'text-align-center font-weight-500 font-size-16'.classNames();
const reserved_dirs = ['wp-admin', 'wp-content', 'wp-includes'];

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

export function HostInstaller({configs={}, slots}) {

	const {ajaxToast} = useContext(ContextToast);
	const {user={}} = window[data_pointer];
	const navigate = useNavigate();

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
		host_id: getRandomString(),
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

	const FileControl=({file})=>{
		return <div className={'d-flex align-items-center column-gap-8'.classNames()} onClick={e=>e.stopPropagation()}>
			<ToggleSwitch 
				checked={isPluginNetWide(file.file_id, false)}
				onChange={checked=>{
					setState({
						...state,
						values: {
							...state.values,
							plugins: state.values.plugins.map(p=>{
								return {
									...p,
									network: p.file_id==file.file_id ? checked : p.network
								}
							})
						}
					})
				}}
			/>
			<span>
				{__('Network Wide')}
			</span>
		</div>
	}

	const isPluginNetWide=(file_id, def)=>{
		return state.values.plugins?.find?.(p=>p.file_id==file_id)?.network ?? def;
	}

	const downloadWP=()=>{

		if ( reserved_dirs.indexOf(state.values.directory_name) > -1 ) {
			alert(__('Directory name is invalid! Please change it first.'));
			return;
		}

		confirm(
			__('Start Setup?'),
			__('Once started, keep the tab open until completion.'),
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

		request('initBaseInstance', {configs: {...state.values, ...params}, host_id: state.host_id}, resp=>{

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

		request('deployNetworkConfigs', {host_id: state.host_id}, resp=>{

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
				request('multisiteSetupComplete', {host_id: state.host_id}, resp=>{
					if ( !resp.success ) {
						ajaxToast(resp);
					} else {
						// Reload the page after multisite setup completed
						navigate(`/`, {replace: true});
					}
				});
			}
		}

		return () => {
			delete window._slds_deployment_hook;
		}
	}, []);

	const has_empty = field_keys.filter(name=>!fields[name].optional && isEmpty(state.values[name])).length;
	const form = slots >= 1 && ! applyFilters( 'slds_multi', false );

	if ( form ) {
		return <div className={section_class + 'text-align-center'.classNames()} style={{padding: '30px 10px'}}>
			<strong className={'d-block font-size-16 color-text-90 margin-bottom-15'.classNames()}>
				{__('Multiple multsite is a Pro feature')}
			</strong>
			<a 
				href='https://solidie.com/live-demo-sandbox-pro/'
				target='_blank'
				className={'button button-primary'.classNames()}
			>
				{__('Upgrade Now')}
			</a>
		</div>
	}

	return <div style={{margin: '50px auto', maxWidth: '800px'}}>
		<div 
			style={{padding: '40px 20px'}} 
			className={'bg-color-white border-radius-8 border-1 b-color-text-10'.classNames()}
		>
			{
				state.step !== null ? <div className={'padding-vertical-15 margin-bottom-15'.classNames()}>
					<LoadingIcon show={true} center={true}/>
					<div className={'text-align-center color-error font-size-13 margin-top-10'.classNames()}>
						<i>{__('Do not close this screen until the process is complete.')}</i>
					</div>
				</div> 
				: 
				<div>
					{
						field_keys.map(name=>{

							const {
								type='text', 
								label, 
								modifier, 
								removable, 
								WpMedia, 
								maxlength
							} = fields[name];

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
											value={state.values[name] || null}
											removable={removable}
											FileControl={name==='plugins' ? FileControl : null}
											onChange={v=>{
												setVal(
													name, 
													name=='theme' ? v : v.map(p=>{
														return {
															...p,
															network: isPluginNetWide(p.file_id, true)
														}
													})
												)
											}}
										/>
									}

									{
										(name !== 'plugins' || (state.values[name]?.length || 0)<2) ? null :
										<span className={'d-block font-size-13 color-text-70 margin-top-5'.classNames()}>
											{__('Plugins will be activated according to the order you see')}
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
						<Link 
							to="/"
							className={'button button-outlined'.classNames()}
							onClick={getBack}
						>
							{__('Cancel')}
						</Link>
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
						{
							!state.iframe_url ? 
								<div className={'text-align-center color-error'.classNames()} style={{marginTop: '30px'}}>
									<i>{__('Something went wrong!')}</i>
								</div> 
								:
								<iframe 
									src={state.iframe_url}
									style={{
										width: '800px', 
										height: `${window.innerHeight - 150}px`
									}} 
								></iframe>
						}
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
	</div>
}
