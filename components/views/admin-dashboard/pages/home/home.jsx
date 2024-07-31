import React, { useContext, useEffect, useState } from "react";
import { Link, useNavigate, useParams } from 'react-router-dom';

import { __, getBack } from "solidie-materials/helpers.jsx";
import { request } from "solidie-materials/request.jsx";
import { ContextToast } from "solidie-materials/toast/toast.jsx";

import {HostInfoSingle, HostInstance, section_class} from './host-instance.jsx';
import { HostInstaller } from "./host-installer";
import { Promotional } from "./promotional.jsx";
import { LoadingIcon } from "solidie-materials/loading-icon/loading-icon.jsx";

const new_host = 'new-host';

export function HomeBackend(props) {

	const {configs={}, meta_data={}} = props;
	const {is_apache} = meta_data;
	const {port} = window.location;
	
	const {ajaxToast} = useContext(ContextToast);
	const {sub_path} = useParams();
	const navigate = useNavigate();


	const [state, setState] = useState({
		hosts: props.hosts || {},
		fetching: true
	});

	const getHosts=()=>{

		setState({
			...state,
			fetching: true
		});

		request('getHosts', {}, resp=>{
			
			const {success, data:{hosts={}}} = resp;

			if ( ! success ) {
				ajaxToast(resp);
				return;
			}

			setState({
				...state,
				hosts,
				fetching: false
			});
		})
	}

	useEffect(()=>{
		if ( ! sub_path ) {
			getHosts();
		}
	}, [sub_path]);

	const {hosts={}} = state;
	const host_ids = Object.keys(hosts);
	const show_installer = sub_path === new_host;
	const host_id   = show_installer ? null : ( hosts[sub_path] ? sub_path : null );

	let page_title  = __( 'Live Demo Sandbox' );
	let page_name   = '';

	if ( show_installer ) {
		page_title = __( 'Configure New Multisite' );
		page_name  = 'installer';

	} else if ( !sub_path ) {
		page_title = __('Multisites');
		page_name  = 'hosts';

	} else if( sub_path ) {
		page_title = __('Multisite Stats');
		page_name  = 'sandboxes';
	}

	return <div style={{margin: '50px auto', maxWidth: '800px'}}>

		<div className={'d-flex align-items-center column-gap-8 margin-bottom-20'.classNames()}>
			{
				!sub_path ? null :
				<Link 
					to="/"
					onClick={getBack}
					className={'sicon sicon-arrow-left font-size-24 color-text-70 interactive'.classNames()}
				/>
			}
			
			<strong className={'d-block font-weight-600 font-size-24'.classNames()}>
				{page_title}
			</strong>

			{
				page_name !== 'hosts' ? null :
				<Link
					to={`/${new_host}/`}
					title={__('Add New Host')}
					className={'sicon sicon-add-circle font-size-24 color-material-80 interactive'.classNames()}
				/>
			}
		</div>
		
		{
			page_name !== 'installer' ? null : 
			<HostInstaller 
				configs={configs} 
				slots={host_ids.length}
			/>
		}

		{
			page_name !== 'sandboxes' ? null :
			<div>
				<HostInstance 
					host_id={host_id}
					configs={hosts[host_id]} 
					meta_data={meta_data}
				/>
			</div>
		}

		{
			page_name !== 'hosts' ? null :
			<>
				{
					host_ids.map((host_id) => {
						return <HostInfoSingle 
							key={host_id} 
							singular={false}
							configs={hosts[host_id]} 
							onDelete={getHosts}
						/>
					})
				}

				{
					host_ids.length ? null :
					<div 
						style={{padding: '40px 20px'}} 
						className={section_class}
					>
						{
							state.fetching ? 
							<LoadingIcon show={true} center={true}/> :
							<div className={'padding-vertical-15 margin-bottom-15 text-align-center'.classNames()}>
								<span className={'d-block margin-bottom-15 font-size-16 font-weight-400 color-text-80'.classNames()}>
									{__('No multsite was found to create demo under.')}
								</span>
								<button 
									className={'button button-primary'.classNames()} 
									onClick={()=>navigate(`/${new_host}/`)}
									disabled={!is_apache || port}
								>
									{__('Create One')}
								</button>
							</div> 
						}
					</div> 
				}
			</>
		}

		{
			! port ? null :
			<div className={'font-size-14 color-error text-align-center margin-bottom-20'.classNames()}>
				<i>{__('Sandbox is only supported for sites without a port in the URL.')}</i>
			</div>
		}

		{
			is_apache ? null :
			<div className={'font-size-14 color-error text-align-center margin-bottom-20'.classNames()}>
				<i>{__('Sandbox functionality is currently supported only on Apache servers.')}</i>
			</div>
		}

		{
			page_name !== 'hosts' ? null : <Promotional/>
		}
	</div>
}
