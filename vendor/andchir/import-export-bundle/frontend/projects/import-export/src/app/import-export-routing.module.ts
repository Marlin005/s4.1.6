import {NgModule} from '@angular/core';
import {Routes, RouterModule} from '@angular/router';

import {ImportComponent} from './import/import.component';
import {ExportComponent} from './export/export.component';
import {ImportExportComponent} from './import-export.component';

const routes: Routes = [
    {
        path: '',
        component: ImportExportComponent,
        children: [
            {
                path: '',
                redirectTo: 'import'
            },
            {
                path: 'import',
                component: ImportComponent
            },
            {
                path: 'export',
                component: ExportComponent
            }
        ]
    },
];

@NgModule({
    imports: [RouterModule.forChild(routes)],
    exports: [RouterModule]
})
export class ImportExportRoutingModule {
}
