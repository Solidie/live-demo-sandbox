import React from "react";
import { Link, useNavigate, useParams } from 'react-router-dom';

import { __, getBack } from "crewhrm-materials/helpers.jsx";

import {HostInfoSingle, HostInstance} from './host-instance.jsx';
import { HostInstaller } from "./host-installer";

export function HomeBackend(props) {

	const {hosts={}, configs={}, meta_data={}} = props;
	const {is_apache} = meta_data;
	const host_ids = Object.keys(hosts);
	const {port} = window.location;
	
	const {sub_path} = useParams();
	const navigate = useNavigate();

	const show_form = sub_path === 'new-host';
	const host_id   = show_form ? null : ( host_ids.length === 1 ? host_ids[0] : ( hosts[sub_path] ? sub_path : null ) );

	return <div style={{margin: '50px auto', maxWidth: '800px'}}>

		<div className={'d-flex align-items-center column-gap-8 margin-bottom-20'.classNames()}>
			{
				!sub_path ? null :
				<Link 
					to="/"
					onClick={getBack}
					className={'ch-icon ch-icon-arrow-left font-size-24 color-text-70 interactive'.classNames()}
				/>
			}
			
			<strong className={'d-block font-weight-600 font-size-24'.classNames()}>
				{show_form ? __( 'Configure New Host' ) : __('Live Demo Sandbox')}
			</strong>
		</div>
		
		{
			!show_form ? null : <HostInstaller configs={configs}/>
		}

		{
			(!host_id || show_form) ? null :
			<div>
				<HostInstance 
					host_id={host_id}
					configs={hosts[host_id]} 
					meta_data={meta_data}
					onAdd={()=>navigate('/new-host/')}
				/>
			</div>
		}

		{
			(host_ids.length < 2 || show_form || host_id)? null :
			host_ids.map((host_id) => {
				return <HostInfoSingle 
					key={host_id} 
					singular={false}
					configs={hosts[host_id]} 
					onDelete={()=>window.location.reload()}
				/>
			})
		}

		{
			(host_ids.length !== 0 || show_form) ? null :
			<>
				<div 
					style={{padding: '40px 20px'}} 
					className={'bg-color-white border-radius-8 border-1 b-color-text-10'.classNames()}
				>
					<div className={'padding-vertical-15 margin-bottom-15 text-align-center'.classNames()}>
						<span className={'d-block margin-bottom-15 font-size-16 font-weight-400 color-text-80'.classNames()}>
							{__('No environment is setup yet to create sandbox under.')}
						</span>
						<button 
							className={'button button-primary'.classNames()} 
							onClick={()=>navigate('/new-host/')}
							disabled={!is_apache || port}
						>
							{__('Set up now')}
						</button>
					</div> 
				</div> 
			</>
		}

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
