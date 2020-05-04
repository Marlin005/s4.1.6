import {FileModel} from '@app/models/file.model';
import {FileData} from '@app/catalog/models/file-data.model';
import {Properties} from '@app/models/properties.iterface';
import {FieldOption} from './import-configuration.model';

export class ExportConfiguration {

    static setOptiontDefaults(exportConfiguration: ExportConfiguration): void {
        const options = {
            parentId: 0,
            contentType: '',
            categoryType: 'column',
            categoriesSeparator: '',
            csvSeparator: ';',
            csvEncoding: 'UTF-8',
            csvEnclosure: '"'
        };
        if (!exportConfiguration.options) {
            exportConfiguration.options = {};
        }
        exportConfiguration.options = Object.assign(options, exportConfiguration.options);
    }

    constructor(
        public id: number,
        public title: string,
        public fileData?: FileData,
        public type?: string,
        public fileSize?: number,
        public options?: Properties,
        public fieldsOptions?: FieldOption[]
    ) {
        if (typeof this.options === 'undefined') {
            ExportConfiguration.setOptiontDefaults(this);
        }
    }
}
