import {NgModule} from '@angular/core';
import {Routes, RouterModule} from '@angular/router';

import {ImportComponent} from './import/import.component';
import {ExportComponent} from './export/export.component';
import {NotFoundComponent} from '@app/not-found.component';
import {ImportExportComponent} from './import-export.component';

// const routes: Routes = [
//     {
//         path: '',
//         redirectTo: 'import',
//         pathMatch: 'full'
//     },
//     {
//         path: 'import',
//         component: ImportComponent
//     },
//     {
//         path: 'export',
//         component: ExportComponent
//     },
//     {
//         path: '**',
//         component: NotFoundComponent
//     }
// ];

const routes: Routes = [
    {
        path: '',
        component: ImportExportComponent,
        children: [
            {
                path: '',
                redirectTo: 'import/'
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
    imports: [RouterModule.forRoot(routes, {useHash: true})],
    exports: [RouterModule]
})
export class AppRoutingModule {
}
