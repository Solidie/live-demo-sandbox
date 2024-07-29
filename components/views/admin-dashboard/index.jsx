import React from "react";
import {createRoot} from 'react-dom/client';

import { MountPoint } from 'solidie-materials/mountpoint.jsx';
import { getElementDataSet } from 'solidie-materials/helpers.jsx';
import {WpDashboardFullPage} from 'solidie-materials/backend-dashboard-container/full-page-container.jsx';
import { HomeBackend } from "./pages/home/home.jsx";
import { HashRouter, Route, Routes } from "react-router-dom";

const home = document.getElementById('Solidie_Sandbox_Backend_Dashboard');

if ( home ) {
	createRoot(home).render(
		<MountPoint>
			<WpDashboardFullPage>
				<div className={'padding-15'.classNames()}>
					<HashRouter>
						<Routes>
							<Route path="/:sub_path?/" element={<HomeBackend {...getElementDataSet(home)}/>}/>
						</Routes>
					</HashRouter>
				</div>
			</WpDashboardFullPage>
		</MountPoint>
	);
}
