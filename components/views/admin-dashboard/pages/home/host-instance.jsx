import React, { useContext, useState } from "react";

import {__} from 'crewhrm-materials/helpers.jsx';
import { confirm } from "crewhrm-materials/prompts.jsx";
import { request } from "crewhrm-materials/request.jsx";
import { ContextToast } from "crewhrm-materials/toast/toast.jsx";
import { LoadingIcon } from "crewhrm-materials/loading-icon/loading-icon.jsx";

export function HostInstance({configs={}}) {

	const {ajaxToast} = useContext(ContextToast); 
	const {dashboard_url} = configs;

	const [state, setState] = useState({
		deleting_host: false
	})

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

	return <div style={{margin: '50px auto', maxWidth: '600px'}}>
		<div className={'d-flex align-items-center column-gap-15 bg-color-white box-shadow-thin padding-vertical-20 padding-horizontal-15 border-radius-8 margin-bottom-20'.classNames()}>
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

		<div className={'bg-color-white box-shadow-thin padding-vertical-20 padding-horizontal-15 border-radius-8'.classNames()}>
			<span className={'font-size-16 font-weight-600 margin-bottom-15'.classNames()}>
				{__('Sandboxes')}
			</span>
			<table className={'table no-responsive'.classNames()}>
				<thead>
					<tr>
						<th>Site Title</th>
						<th>User IP</th>
						<th></th>
					</tr>
				</thead>
				<tbody>

				</tbody>
			</table>
		</div>
	</div> 
}
