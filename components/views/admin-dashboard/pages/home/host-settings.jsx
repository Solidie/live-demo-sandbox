import React, { useContext, useState } from "react";

import { __, isEmpty } from "solidie-materials/helpers.jsx";
import { TextField } from "solidie-materials/text-field/text-field.jsx";
import { NumberField } from "solidie-materials/number-field/number-field.jsx";
import { DropDown } from "solidie-materials/dropdown/dropdown.jsx";
import { ToggleSwitch } from "solidie-materials/toggle-switch/ToggleSwitch.jsx";
import { request } from "solidie-materials/request.jsx";
import { ContextToast } from "solidie-materials/toast/toast.jsx";
import { LoadingIcon } from "solidie-materials/loading-icon/loading-icon.jsx";

const fields = [
	{
		label: __('Concurrency Limit'),
		name: 'concurrency_limit',
		type: 'number',
		min: 1
	},
	{
		label: __('Delete after inactivity of'),
		name: 'group_key',
		fields: [
			{
				name: 'inactivity_time_allowed',
				type: 'number',
				min: 1
			},
			{
				name: 'inactivity_period_allowed',
				type: 'dropdown',
				clearable: false,
				options: [
					{
						id: 'minute',
						label: __('Minute')
					},
					{
						id: 'hour',
						label: __('Hour')
					}
				]
			}
		]
	},
	{
		label: __('Site Title'),
		name: 'sandbox_site_title',
		type: 'text'
	},
	{
		label: <>
				{__('Role to create a user with (if need)')}<br/>
				<small>{__('Applicable for new demo sites only')}</small>
			</>,
		name: 'new_user_role',
		type: 'text'
	},
	{
		switch_label: __('Auto login to the user'),
		name: 'auto_login_new_user',
		type: 'switch'
	}
];

function RenderField({field:{min, name, value, type, options, switch_label, clearable}, onChange}) {

	if ( type === 'text' ) {
		return <TextField 
			onChange={v=>onChange(name, v)} 
			value={value}
			type={type}
		/>
	}

	if ( type === 'number' ) {
		return <NumberField 
			onChange={v=>onChange(name, v)} 
			value={value}
			type={type}
			min={min}
		/>
	}

	if ( type === 'dropdown' ) {
		return <DropDown
			options={options}
			value={value}
			onChange={v=>onChange(name, v)}
			clearable={clearable}
		/>
	}

	if ( type === 'switch' ) {
		return <div className={'d-flex align-items-center column-gap-8'.classNames()}> 
			<ToggleSwitch
				checked={value}
				onChange={v=>onChange(name, v)}
			/>
			<span>
				{switch_label || __('Enable')}
			</span>
		</div>
	}

	return null;
}

export function HostSettings({closePanel, settings={}, host_id}) {

	const {ajaxToast} = useContext(ContextToast);

	const [state, setState] = useState({
		saving: false,
		values: {
			...settings
		}
	});

	const onChange=(name, value)=>{
		setState({
			...state,
			values: {
				...state.values,
				[name]: value
			}
		});
	}

	const saveSettings=()=>{

		setState({
			...state,
			saving: true
		});
		
		request('saveSandboxSettings', {settings: state.values, host_id}, resp=>{

			if ( resp.success ) {
				window.location.reload();
				return;
			}
			
			setState({
				...state,
				saving: false
			});
			
			ajaxToast(resp);
		});
	}

	return <div>

		<strong 
			className={'d-block font-size-18 color-text-80'.classNames()}
			style={{marginBottom: '35px'}}
		>
			{__('Demo Site Settings')}
		</strong>

		{
			fields.map((field)=>{

				const {label, fields, name} = field;

				return <div key={name} className={'d-flex align-items-center margin-bottom-25'.classNames()}>
					<div style={{width: '250px'}}>
						<strong className={'d-block font-size-14 font-weight-500 color-text-60'.classNames()}>
							{label}
						</strong>
					</div>
					<div className={'flex-1'.classNames()}>
						{
							!fields ? 
								(
									(name==='auto_login_new_user' && isEmpty(state.values.new_user_role)) ? null :
									<RenderField 
										field={{...field, value: state.values[name] || ''}} 
										onChange={onChange}
									/>
								)
								:
								<div className={'d-flex align-items-center column-gap-15'.classNames()}>
									{
										fields.map(field=>{
											return <div key={field.name} className={'flex-1'.classNames()}>
												<RenderField 
													field={{...field, value: state.values[field.name] || ''}} 
													onChange={onChange}
												/>
											</div>
										})
									}
								</div>
						}
					</div>
				</div>
			})
		}

		<div className={'d-flex align-items-center column-gap-15 justify-content-flex-end'.classNames()}>
			<button className={'button button-outlined button-small'.classNames()} onClick={closePanel}>
				{__('Cancel')}
			</button>
			<button className={'button button-primary button-small'.classNames()} onClick={saveSettings} disabled={state.saving}>
				{__('Save Settings')} <LoadingIcon show={state.saving}/>
			</button>
		</div>
	</div>
}