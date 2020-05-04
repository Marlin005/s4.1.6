import {FileModel} from '@app/models/file.model';
import {FileData} from '@app/catalog/models/file-data.model';
import {Properties} from '@app/models/properties.iterface';

export interface FieldOption {
    sourceName?: string;
    sourceTitle?: string;
    targetAction?: string;
    targetName?: string;
    targetTitle?: string;
    separator?: string;
    options?: FieldOption[];
}

export interface ImportTestData {
    categories: string[];
    data: {[key: string]: string|number};
    memory_peak_usage: string;
    time_execution: number;
    row_number_first: number;
}

export class ImportConfiguration {

    static setOptiontDefaults(importConfiguration: ImportConfiguration): void {
        const options = {
            parentId: 0,
            rowNumberHeaders: 1,
            rowNumberFirst: 2,
            rowNumberLast: 0,
            stepsNumber: 1,
            step: 1,
            categoriesSeparator: '',
            sheetName: '',
            contentType: '',
            articulFieldName: '',
            aliasAdditionalFieldName: '',
            csvSeparator: ';',
            csvEncoding: 'UTF-8',
            csvEnclosure: '"',
            skipFound: false,
            filesDownload: false
        };
        if (!importConfiguration.options) {
            importConfiguration.options = {};
        }
        importConfiguration.options = Object.assign(options, importConfiguration.options);
    }

    constructor(
        public id: number,
        public title: string,
        public fileData?: FileData,
        public type?: string,
        public fileSize?: number,
        public rowsQuantity?: number,
        public sheetsQuantity?: number,
        public sheetsNames?: string[],
        public options?: Properties,
        public fieldsOptions?: FieldOption[]
    ) {
        if (typeof this.options === 'undefined') {
            ImportConfiguration.setOptiontDefaults(this);
        }
    }
}
