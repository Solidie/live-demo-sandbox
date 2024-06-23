import React from "react";
import {createRoot} from 'react-dom/client';

import { MountPoint } from 'crewhrm-materials/mountpoint.jsx';
import { getElementDataSet } from 'crewhrm-materials/helpers.jsx';
import {WpDashboardFullPage} from 'crewhrm-materials/backend-dashboard-container/full-page-container.jsx';
import { HomeBackend } from "./pages/home/home.jsx";

const home = document.getElementById('Solidie_Sandbox_Backend_Dashboard');
console.log(home)
if ( home ) {
	createRoot(home).render(
		<MountPoint>
			<WpDashboardFullPage>
				<HomeBackend/>
			</WpDashboardFullPage>
		</MountPoint>
	);
}
