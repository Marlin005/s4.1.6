import {NgModule} from '@angular/core';
import {CommonModule} from '@angular/common';

import {SharedModule} from '@app/shared.module';
import {CategoriesService} from '@app/catalog/services/categories.service';
import {SettingsService} from '@app/settings/settings.service';
import {SystemNameService} from '@app/services/system-name.service';
import {AppComponent} from './app.component';
import {DefaultComponent} from './default/default.component';
import {ImportComponent} from './import/import.component';
import {ExportComponent} from './export/export.component';
import {ModalImportContentComponent} from './import/modal-import-content.component';
import {ModalExportContentComponent} from './export/modal-export-content.component';
import {ImportExportComponent} from './import-export.component';
import {ImportExportRoutingModule} from './import-export-routing.module';

import {ImportService} from './services/import-service';
import {ExportService} from './services/export-service';

@NgModule({
    imports: [
        CommonModule,
        SharedModule,
        ImportExportRoutingModule
    ],
    declarations: [
        AppComponent,
        ImportExportComponent,
        DefaultComponent,
        ImportComponent,
        ExportComponent,

        ModalImportContentComponent,
        ModalExportContentComponent
    ],
    entryComponents: [
        ModalImportContentComponent,
        ModalExportContentComponent
    ],
    providers: [
        SettingsService,
        SystemNameService,
        ImportService,
        ExportService,
        CategoriesService
    ]
})
export class ImportExportModule {
}
