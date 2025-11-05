import { Building2 } from 'lucide-react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../ui-new//select';
import { Company } from '../../types/schedule';

interface CompanySelectorProps {
  companies: Company[];
  selectedCompanyId: number;
  onCompanyChange: (companyId: number) => void;
}

export function CompanySelector({ companies, selectedCompanyId, onCompanyChange }: CompanySelectorProps) {
  return (
    <div className="bg-white p-4 rounded-lg border">
      <div className="flex items-center gap-4">
        <Building2 className="h-5 w-5 text-gray-600" />
        <label className="text-sm">Empresa:</label>
        <Select
          value={selectedCompanyId.toString()}
          onValueChange={(value) => onCompanyChange(Number(value))}
        >
          <SelectTrigger className="w-[250px]">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            {companies.map(company => (
              <SelectItem key={company.id} value={company.id.toString()}>
                {company.name}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>
    </div>
  );
}
