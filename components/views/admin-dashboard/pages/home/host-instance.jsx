import React, { useContext, useEffect, useState } from "react";

import {__, copyToClipboard} from 'crewhrm-materials/helpers.jsx';
import { confirm } from "crewhrm-materials/prompts.jsx";
import { request } from "crewhrm-materials/request.jsx";
import { ContextToast } from "crewhrm-materials/toast/toast.jsx";
import { LoadingIcon } from "crewhrm-materials/loading-icon/loading-icon.jsx";
import { TableStat } from "crewhrm-materials/table-stat.jsx";

const section_class = 'bg-color-white box-shadow-thin padding-vertical-20 padding-horizontal-15 border-radius-8 margin-bottom-20'.classNames();

export function HostInstance({configs={}}) {

	const {ajaxToast, addToast} = useContext(ContextToast); 
	const {dashboard_url, sandbox_url} = configs;

	const [state, setState] = useState({
		deleting_host: false,
		deleting_sandboxes: [],
		fetching: true,
		sandboxes: [],
		filters: {
			page: 1
		}
	});

	const getSandboxes=()=>{

		setState({
			...state,
			fetching: true,
		});

		request('getSandboxes', {filters: state.filters}, resp=>{
			
			const {sandboxes=[]} = resp.data;

			setState({
				...state,
				fetching: false,
				sandboxes
			});
		});
	}

	useEffect(()=>{
		getSandboxes();
	}, []);

	const deleteInstance=()=>{
		confirm(
			__('Sure to delete the host?'),
			__('Proceeding will delete all the sandbox instances too. You\'ll be able to set up again then.'),
			()=>{
				setState({
					...state,
					deleting_host: true
				});
				
				request('deleteEntireHost', {}, resp=>{
					if ( resp.success ) {
						window.location.reload();
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

	const deleteSandbox=(sandbox_id)=>{
		confirm(
			__('Sure to delete?'),
			__('The user will loose access immediately.'),
			()=>{
				setState({
					...state,
					deleting_sandboxes:[...state.deleting_sandboxes, sandbox_id],
				});

				request('deleteSandbox', {sandbox_id}, resp=>{

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

	return <div style={{margin: '50px auto', maxWidth: '600px'}}>
		<div className={'d-flex align-items-center column-gap-15'.classNames() + section_class}>
			<div className={'flex-1'.classNames()}>
				<span className={'font-size-18 font-weight-700'.classNames()}>
					{__('Sandbox Host')}
				</span>
			</div>
			<div>
				{
					state.deleting_host ? <LoadingIcon show={true}/> :
					<i 
						className={'ch-icon ch-icon-trash color-error interactive cursor-pointer font-size-18'.classNames()}
						onClick={deleteInstance}
					></i>
				}
				
			</div>
			<div>
				<a 
					className={'button button-primary'.classNames()} 
					href={dashboard_url}
					target="_blank"
				>
					{__('Dashboard')}
				</a>
			</div>
		</div>

		<div className={section_class}>
			<div className={'d-flex align-items-center column-gap-8 margin-bottom-10'.classNames()}>
				<div>
					<span className={'font-size-16 font-weight-600 margin-bottom-15'.classNames()}>
						{__('Sandboxes')}
					</span>
				</div>
				<div className={'d-flex'.classNames()}>
					{
						state.fetching ? 
							<LoadingIcon show={true}/> :
							<i 
								className={'ch-icon ch-icon-reload color-material-90 interactive cursor-pointer'.classNames()}
								onClick={getSandboxes}
								title={__('Refresh Sandbox List')}
							></i>
					}
				</div>
			</div>
			
			<table className={'table no-responsive'.classNames()}>
				<thead>
					<tr>
						<th>Instance</th>
						<th>User IP</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					{
						state.sandboxes.map(sandbox=>{

							const {
								sandbox_id, 
								site_title, 
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
									<div className={'d-flex align-items-center justify-content-flex-end column-gap-8'.classNames()}>
										{
											state.deleting_sandboxes.indexOf(sandbox_id)>-1 ? <LoadingIcon show={true}/> :
											<i 
												className={'ch-icon ch-icon-trash color-error interactive cursor-pointer font-size-14'.classNames()}
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
		</div>

		<div className={section_class}>
			<span className={'d-block font-size-14 font-weight-600 margin-bottom-15'.classNames()}>
				{__('Live Demo URL')}
			</span>

			<div className={'d-flex align-items-center column-gap-15 bg-color-material-3 border-radius-10 padding-15 border-1 b-color-material-7'.classNames()}>	
				<div className={'flex-1 font-weight-500 font-size-13'.classNames()} style={{wordBreak: 'break-all'}}>
					{sandbox_url}
				</div>
				<div className={'d-flex'.classNames()}>
					<i 
						className={'ch-icon ch-icon-copy font-size-20 cursor-pointer'.classNames()}
						onClick={()=>copyToClipboard(sandbox_url, addToast)}
					></i>
				</div>
			</div>
		</div>
	</div> 
}
