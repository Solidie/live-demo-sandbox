import React, { useContext, useEffect, useState } from "react";

import {__, copyToClipboard, data_pointer, sprintf, timeAgoOrAfter} from 'crewhrm-materials/helpers.jsx';
import { confirm } from "crewhrm-materials/prompts.jsx";
import { request } from "crewhrm-materials/request.jsx";
import { ContextToast } from "crewhrm-materials/toast/toast.jsx";
import { LoadingIcon } from "crewhrm-materials/loading-icon/loading-icon.jsx";
import { TableStat } from "crewhrm-materials/table-stat.jsx";
import { Modal } from "crewhrm-materials/modal.jsx";
import { Pagination } from "crewhrm-materials/pagination/pagination.jsx";

import { HostSettings } from "./host-settings.jsx";

const section_class = 'bg-color-white box-shadow-thin padding-20 border-radius-8 margin-bottom-20'.classNames();

export function HostInstance({configs={}}) {

	const {ajaxToast, addToast} = useContext(ContextToast); 
	const {dashboard_url, sandbox_url, settings={}} = configs;

	const [state, setState] = useState({
		deleting_host: false,
		deleting_sandboxes: [],
		fetching: true,
		sandboxes: [],
		show_settings: false,
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

		request('getSandboxes', state.filters, resp=>{
			
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

	const toggleSettingsModal=(show)=>{
		setState({
			...state,
			show_settings: show
		});
	}

	return <div style={{margin: '50px auto', maxWidth: '850px'}}>
		{
			!state.show_settings ? null :
			<Modal>
				<HostSettings 
					closePanel={()=>toggleSettingsModal(false)}
					settings={settings}
				/>
			</Modal>
		}

		<div className={'d-flex align-items-center column-gap-15'.classNames() + section_class}>
			<div className={'flex-1'.classNames()}>
				<span className={'font-size-18 font-weight-700'.classNames()}>
					{__('Sandbox Host')}
				</span>
			</div>
			<div>
				{
					state.deleting_host ? <LoadingIcon show={true}/> :
					<div className={'d-flex align-items-center column-gap-15'.classNames()}>
						<i 
							className={'ch-icon ch-icon-trash color-error interactive cursor-pointer font-size-18'.classNames()}
							onClick={deleteInstance}
						></i>

						<i 
							className={'ch-icon ch-icon-settings-gear color-text interactive cursor-pointer font-size-18'.classNames()}
							onClick={()=>toggleSettingsModal(true)}
						></i>
					</div>
					
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
			<div className={`${state.segmentation?.page_count > 1 ? 'margin-top-15' : ''}`.classNames()}>
				<Pagination
					onChange={page=>setFilter('page', page)}
					pageNumber={state.filters.page}
					pageCount={state.segmentation.page_count}
				/>
			</div>
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

		
		<div className={section_class + 'border-1 b-color-text-10 box-shadow-thick'.classNames()}>
			<span className={'d-block font-size-16 font-weight-500 color-text-90 margin-bottom-15'.classNames()}>
				{sprintf(__('Hi %s'), window[data_pointer].user.display_name)},
			</span>

			<span className={'d-block font-size-14 font-weight-400 color-text-70 margin-bottom-5'.classNames()}>
				{__('What if you could showcase and sell your themes and plugins directly from your own website? Imagine having the ability to manage new version releases, auto updates, licenses, and documentation all in one convenient place.')}
			</span>

			<span className={'d-block font-size-14 font-weight-400 color-text-70 margin-bottom-25'.classNames()}>
				{__('Introducing')} <strong className={'font-weight-700'.classNames()}>{__('Solidie')}</strong>, &nbsp;
				{__('the ultimate plugin that transforms a simple WordPress website into a full-fledged digital content marketplace.')}
			</span>

			<div className={'text-align-center margin-bottom-25'.classNames()}>
				<a href='https://solidie.com/' target='_blank' className={'button button-primary'.classNames()}>
					{__('Learn More')}
				</a>
			</div>

			<span className={'d-block font-size-14 font-weight-400 color-text-70'.classNames()}>
				{__('You can also sell any type of content, such as audio, video, images, apps, fonts, 3d models and more, all from your own website. Plus earn commissions from third party contributors too.')}
			</span>
		</div>
	</div> 
}
