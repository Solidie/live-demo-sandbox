import React, { useContext } from "react";
import { useNavigate, useParams } from 'react-router-dom';

import { ContextToast } from "crewhrm-materials/toast/toast.jsx";
import { __, data_pointer } from "crewhrm-materials/helpers.jsx";

import {HostInstance} from './host-instance.jsx';
import { HostInstaller } from "./host-installer";

import style from './style.module.scss';

export function HomeBackend(props) {

	const {hosts={}, configs={}, meta_data={}} = props;
	const {ajaxToast} = useContext(ContextToast);
	const {user={}} = window[data_pointer];
	const {is_apache} = meta_data;
	const host_ids = Object.keys(hosts);
	const {port} = window.location;
	
	const {sub_path} = useParams();
	const navigate = useNavigate();

	const show_form = sub_path === 'new-host';
	const host_id   = show_form ? null : ( host_ids.length === 1 ? host_ids[0] : ( hosts[sub_path] ? sub_path : null ) );

	return <div style={{margin: '50px auto', maxWidth: '800px'}}>

		<strong className={'d-block font-weight-600 font-size-24 margin-bottom-20'.classNames()}>
			{__('Live Demo Sandbox')}
		</strong>
		
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
					onBack={host_ids.length>1}
					onAdd={()=>navigate('/new-host/')}
				/>
			</div>
		}

		{
			(host_ids.length < 2 || show_form || host_id)? null :
			<div
				className={'border-1 b-color-text-20 border-radius-10 bg-color-white'.classNames()}
			>
				{host_ids.map((host_id, index) => {

					const { site_title } = hosts[host_id];
					const is_last = index === host_ids.length - 1;

					return <div
						key={host_id}
						className={
							`d-flex align-items-center column-gap-10 cursor-pointer padding-vertical-10 padding-horizontal-15 ${!is_last ? 'border-bottom-1 b-color-text-20' : ''}`.classNames() +
							`single-item`.classNames(style)
						}
						onClick={()=>navigate(`/${host_id}/`)}
					>
						<div className={'flex-1 d-flex align-items-center column-gap-10'.classNames()}>
							<div className={'flex-1'.classNames()}>
								<span
									className={
										'd-block font-size-15 font-weight-500 line-height-25'.classNames()
									}
								>
									{site_title}
								</span>
							</div>
						</div>
						<div>
							<i
								className={
									'ch-icon ch-icon-arrow-right font-size-24'.classNames() +
									`icon`.classNames(style)
								}
							></i>
						</div>
					</div>
				})}
			</div>
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
