import React, { useContext, useEffect, useState } from "react";

import {request} from 'crewhrm-materials/request.jsx';
import { ContextToast } from "crewhrm-materials/toast/toast.jsx";
import { LoadingIcon } from "crewhrm-materials/loading-icon/loading-icon.jsx";

export function HomeBackend() {

	const {ajaxToast} = useContext(ContextToast);

	const [state, setState] = useState({
		step: null,
		iframe_url: null
	});

	const init=()=>{
		
		setState({
			...state,
			step: 1
		});

		request('initBaseInstance', {}, resp=>{

			setState({
				...state,
				step: resp.success ? 2 : null,
				iframe_url: resp.data?.iframe_url || null
			});

			if ( ! resp.success ) {
				ajaxToast(resp);
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
				setState({
					...state,
					step: false
				});
			}
		}

		return () => {
			delete window._slds_deployment_hook;
		}
	}, []);

	if ( state.step === false ) {
		return <div>
			All Done
		</div>
	}

	return <div style={{textAlign: 'center', padding: '50px 0'}}>
		{
			state.step!==1 ? null :
			<div>
				Setting Up Site
			</div>
		}

		{
			(state.step!==2 && state.step!==4) ? null :
			<div style={{margin: '0 auto'}}>
				<iframe style={{width: '800px', height: `${window.innerHeight - 150}px`}} src={state.iframe_url}></iframe>
			</div>
		}

		{
			state.step !== 3 ? null :
			<div>
				Deploying Network Configs
			</div>
		}

		<button 
			className={'button button-primary'.classNames()} 
			disabled={state.step!==null}
			onClick={init}
		>
			Init Setup <LoadingIcon show={state.step!==null}/>
		</button>
	</div>
}
