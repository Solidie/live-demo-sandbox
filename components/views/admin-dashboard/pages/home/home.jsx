import React, { useContext } from "react";

import {request} from 'crewhrm-materials/request.jsx';
import { ContextToast } from "crewhrm-materials/toast/toast.jsx";

export function HomeBackend() {

	const {ajaxToast} = useContext(ContextToast);

	const init=()=>{
		request('initBaseInstance', {}, resp=>{
			ajaxToast(resp);
		});
	}

	return <div style={{textAlign: 'center', padding: '50px 0'}}>
		<button className={'button button-primary'.classNames()} onClick={init}>
			Init Setup
		</button>
	</div>
}
