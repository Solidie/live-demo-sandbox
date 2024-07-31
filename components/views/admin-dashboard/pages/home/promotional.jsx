import React from "react";

import { data_pointer, __ } from "solidie-materials/helpers.jsx";
import { section_class } from "./host-instance.jsx";

import img from '../../../../images/promotional.png';

export function Promotional() {
	return <div className={section_class + 'd-flex align-items-stretched border-1 b-color-text-10 box-shadow-thick'.classNames()} style={{padding: 0, marginTop: '70px'}}>
		<div 
			className={'flex-1'.classNames()} 
			style={{
				backgroundImage: `url(${img})`, 
				backgroundPosition: 'center center', 
				backgroundSize: 'cover'
			}}
		>
		</div>
		<div className={'flex-1'.classNames()} style={{padding: '20px'}}>

			<span className={'d-block font-size-14 font-weight-400 color-text-70 margin-bottom-10'.classNames()}>
				{__('Introducing')} <strong className={'font-weight-700'.classNames()}>{__('Solidie')}</strong>, &nbsp;
				{__('the ultimate plugin that transforms a simple WordPress website into a full-fledged digital content marketplace.')}
			</span>

			<span className={'d-block font-size-14 font-weight-400 color-text-70 margin-bottom-25'.classNames()}>
				{__('Sell your themes and plugins directly from your website. Manage releases, auto updates, licenses, and documentation all in one place.')}
			</span>

			<div className={'text-align-center margin-bottom-25'.classNames()}>
				<a href='https://solidie.com/' target='_blank' className={'button button-small button-outlined'.classNames()}>
					{__('Learn More')}
				</a>
			</div>

			<span className={'d-block font-size-14 font-weight-400 color-text-70'.classNames()}>
				{__('Also sell audio, video, images, fonts, 3D models, and more. Earn commissions from third-party contributors too.')}
			</span>
		</div>
	</div>
}