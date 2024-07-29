import React, { useContext, useEffect, useState } from "react";
import { Link, useNavigate } from "react-router-dom";

import {__, copyToClipboard, data_pointer, sprintf, timeAgoOrAfter} from 'solidie-materials/helpers.jsx';
import { confirm } from "solidie-materials/prompts.jsx";
import { request } from "solidie-materials/request.jsx";
import { ContextToast } from "solidie-materials/toast/toast.jsx";
import { LoadingIcon } from "solidie-materials/loading-icon/loading-icon.jsx";
import { TableStat } from "solidie-materials/table-stat.jsx";
import { Modal } from "solidie-materials/modal.jsx";
import { Pagination } from "solidie-materials/pagination/pagination.jsx";

import { HostSettings } from "./host-settings.jsx";

export const section_class = 'bg-color-white box-shadow-thin padding-20 border-radius-8 margin-bottom-20'.classNames();

export function HostInfoSingle({configs, onDelete, singular}) {

	const {ajaxToast, addToast} = useContext(ContextToast); 

	const [state, setState] = useState({
		deleting_host: false,
		show_settings: false
	});

	const toggleSettingsModal=(show)=>{
		setState({
			...state,
			show_settings: show
		});
	}

	const deleteInstance=()=>{
		confirm(
			__('Sure to delete the host?'),
			__('Proceeding will delete all the sandbox instances too. You\'ll be able to set up again then.'),
			()=>{
				setState({
					...state,
					deleting_host: true
				});
				
				request('deleteEntireHost', {host_id: configs.host_id}, resp=>{
					if ( resp.success ) {
						onDelete();
					} else {
						setState({
							...state,
							deleting_host: false
						});
						ajaxToast(resp);
					}
				});
			}
		);
	}

	return <>
		{
			!state.show_settings ? null :
			<Modal>
				<HostSettings 
					closePanel={()=>toggleSettingsModal(false)}
					settings={configs.settings}
					host_id={configs.host_id}
				/>
			</Modal>
		}

		<div 
			className={'d-flex align-items-center column-gap-15'.classNames() + section_class}
		>
			<div className={'flex-1'.classNames()}>
				<span className={'d-block margin-bottom-15 font-size-18 font-weight-700'.classNames()}>
					{configs.site_title}&nbsp;
					{
						configs.setup_complete ? null : 
						<i className={'color-error font-size-13'.classNames()}>
							{__('Incomplete')}
						</i>
					}
				</span>
				<span 
					className={'font-size-13'.classNames()}
				>
					<span className={'color-text-50 font-weight-500'.classNames()}>
						{__('Demo')}:
					</span>
					&nbsp;
					<span 
						className={'color-text-90 font-weight-400'.classNames()} 
						style={{cursor: 'context-menu', wordBreak: 'break-all'}}
						onClick={e=>{
							copyToClipboard(configs.new_sandbox_url, addToast);
						}}
					>
						{configs.new_sandbox_url}
					</span>
				</span>
			</div>
			<div className={'d-flex align-items-center column-gap-15'.classNames()}>
				{
					state.deleting_host ? <LoadingIcon show={true}/> :
					(
						singular ? 
							<a 
								className={'button button-primary'.classNames()} 
								href={configs.dashboard_url}
								target="_blank"
							>
								{__('Dashboard')}
							</a>
							:
							<>
								<i 
									className={'sicon sicon-trash color-error interactive cursor-pointer font-size-18'.classNames()}
									onClick={e=>{
										deleteInstance();
									}}
								></i>

								<i 
									className={'sicon sicon-settings-gear color-text interactive cursor-pointer font-size-18'.classNames()}
									onClick={e=>{
										toggleSettingsModal(true);
									}}
								></i>

								<Link
									to={`/${configs.host_id}/`}
									className={'button button-small button-primary'.classNames()}
								>
									{__('Details')}
								</Link>
							</>
					)
				}
			</div>
		</div>
	</> 
}

export function HostInstance({host_id, configs={}}) {

	const {ajaxToast} = useContext(ContextToast); 
	const navigate = useNavigate();

	const [state, setState] = useState({
		deleting_host: false,
		deleting_sandboxes: [],
		fetching: true,
		sandboxes: [],
		segmentation: {},
		filters: {
			page: 1
		}
	});

	const setFilter=(name, value)=>{
		setState({
			...state,
			filters: {
				...state.filters,
				[name]: value
			}
		});
	}

	const getSandboxes=()=>{

		setState({
			...state,
			fetching: true,
		});

		request('getSandboxes', {...state.filters, host_id}, resp=>{
			
			const {sandboxes=[], segmentation={}} = resp.data;

			setState({
				...state,
				fetching: false,
				sandboxes,
				segmentation
			});
		});
	}

	useEffect(()=>{
		getSandboxes();
	}, [state.filters]);

	const deleteSandbox=(sandbox_id)=>{
		confirm(
			__('Sure to delete?'),
			__('The user will loose access immediately.'),
			()=>{
				setState({
					...state,
					deleting_sandboxes:[...state.deleting_sandboxes, sandbox_id],
				});

				request('deleteSandbox', {sandbox_id, host_id}, resp=>{

					setState({
						...state,
						deleting_sandboxes: state.deleting_sandboxes.filter(id=>id!=sandbox_id)
					});

					ajaxToast(resp);

					if (resp.success) {
						getSandboxes();
					}
				});
			}
		);
	}

	return <div style={{margin: '50px auto', maxWidth: '800px'}}>
		
		<HostInfoSingle 
			singular={true}
			configs={configs} 
			onDelete={()=>{
				navigate(`/`, {replace: true});
			}}
		/>

		<div className={section_class}>
			<div className={'d-flex align-items-center column-gap-8 margin-bottom-10 justify-content-space-between'.classNames()}>
				<div>
					<span className={'font-size-16 font-weight-600 margin-bottom-15'.classNames()}>
						{__('Sandboxes')}: {__(state.segmentation.total_count)}
					</span>
				</div>
				<div className={'d-flex'.classNames()}>
					{
						state.fetching ? 
							<LoadingIcon show={true}/> :
							<span 
								className={'d-flex align-items-center column-gap-5 color-material-90 interactive cursor-pointer'.classNames()}
								onClick={getSandboxes}
							>
								<i 
									className={'sicon sicon-reload'.classNames()}
									title={__('Refresh Sandbox List')}
								></i> {__('Refresh')}
							</span>
							
					}
				</div>
			</div>
			
			<table className={'table no-responsive'.classNames()}>
				<thead>
					<tr>
						<th>{__('Instance')}</th>
						<th>{__('User IP')}</th>
						<th>{__('Created')}</th>
						<th>{__('Expires')}</th>
						<th>{__('Last Hit')}</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					{
						state.sandboxes.map(sandbox=>{

							const {
								sandbox_id, 
								site_title, 
								created_unix,
								expires_unix,
								last_hit_unix,
								site_id, 
								user_ip, 
								dashboard_url,
							} = sandbox;

							return <tr key={sandbox_id}>
								<td>
									Site {site_id}
								</td>
								<td>
									{user_ip}
								</td>
								<td>
									{timeAgoOrAfter(created_unix)}
								</td>
								<td>
									{timeAgoOrAfter(expires_unix)}
								</td>
								<td>
									{timeAgoOrAfter(last_hit_unix)}
								</td>
								<td>
									<div className={'d-flex align-items-center justify-content-flex-end column-gap-8'.classNames()}>
										{
											state.deleting_sandboxes.indexOf(sandbox_id)>-1 ? <LoadingIcon show={true}/> :
											<i 
												className={'sicon sicon-trash color-error interactive cursor-pointer font-size-14'.classNames()}
												onClick={()=>deleteSandbox(sandbox_id)}
											></i>
										}
										<a className={'button button-outlined button-small'.classNames()} href={dashboard_url} target="_blank">
											{__('Dashboard')}
										</a>
									</div>
								</td>
							</tr>
						})
					}
					<TableStat 
						empty={!state.sandboxes.length}
						loading={state.fetching}
						message={__('No sandbox found')}
					/>
				</tbody>
			</table>
			<div className={`${state.segmentation?.page_count > 1 ? 'margin-top-15' : ''}`.classNames()}>
				<Pagination
					onChange={page=>setFilter('page', page)}
					pageNumber={state.filters.page}
					pageCount={state.segmentation.page_count}
				/>
			</div>
		</div>
	</div> 
}
