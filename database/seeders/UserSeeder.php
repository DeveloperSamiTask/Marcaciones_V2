<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::insert([
            ["name" =>"HCALLE", "password"=>'$2y$12$F2Ut2ZQyjfthuD0IuJdbw.cRheg9aT7nWDBbWe3R8nHVYIETDodSC', "rol_id" => 4, "email" => "hcalle@gmail.com", "empleado_id" => 778, "estado" => 1],
            ["name" =>"JOVALLE", "password"=>'$2y$12$F2Ut2ZQyjfthuD0IuJdbw.cRheg9aT7nWDBbWe3R8nHVYIETDodSC', "rol_id" => 4, "email" => "gerente.administrativo@samitask.com", "empleado_id" => 104, "estado" => 1],
            ["name" =>"DSANCHEZ", "password"=>'$2y$12$ktCaDrfEx.7Je9EOHNHWf.SSxjbPxp7/OcCP1nLd6mSvLFnys3wpK', "rol_id" => 4, "email" => "manto.gs@lagranjavilla.com", "empleado_id" => 2186, "estado" => 0],
            ["name" =>"HGONZALES", "password"=>'$2y$12$7xIKmx2c8E3JFcAdG3GAfeNxyfnLRgQQKsQZmNNLEzy/.ckokNCdC', "rol_id" => 4, "email" => "silvestre.gs@lagranjavilla.com", "empleado_id" => 466, "estado" => 0],
            ["name" =>"MCARBAJAL", "password"=>'$2y$12$VQaxYbobIumwXjONodSHiO8KsQt3BfexI3RPniwgrKuQMXcetOQDW', "rol_id" => 4, "email" => "adm@maderaverdehotel.com.pe", "empleado_id" => 128, "estado" => 1],
            ["name" =>"JRRHH", "password"=>'$2y$12$DyZojkhCBUt7hjkDT.s3.uJICJKqhzl.de7mF9C3vr8IXWfo8EGq2', "rol_id" => 2, "email" => "rrhh@samitask.com", "empleado_id" => 4631, "estado" => 1],
            ["name" =>"RRHH3", "password"=>'$2y$12$KwS4UdvQflEnEi6Dvy0DJeRsNXxy5saMfU/iIq1H2mMKIVYhcLoJG', "rol_id" => 2, "email" => "RRHH.3@SAMITASK.COM", "empleado_id" => 5654, "estado" => 1],
            ["name" =>"gerencia", "password"=>'$2y$12$nYHG8BrqQTCSiVkprZJ2S.Wta6Po.t1vNVWi0vkOim/wDAlsCGhoG', "rol_id" => 1, "email" => "adm@samitask.con", "empleado_id" => 113, "estado" => 1],
            ["name" =>"SISTEMAS1", "password"=>'$2y$12$rhORPFwTIt2tdiyl1ZO47uZf/7Yj7ESoMvkwe11jFozdbAypivHU.', "rol_id" => 4, "email" => "SISTEMAS@GMAIL.COM", "empleado_id" => 4626, "estado" => 1],
            ["name" =>"RPEREZ", "password"=>'$2y$12$eyD8bSONhXztOvlbPke6geBO9JjlxkyS/qlRFg5sXd7SFJEBuG5Su', "rol_id" => 4, "email" => "VENTAS@GMAIL.COM", "empleado_id" => 3481, "estado" => 1],
            ["name" =>"LCHAHUA", "password"=>'$2y$12$yzOsLfupHleYCFiHRrWhmeNBpdJj/0vu16C4AQj/a5F3g9W0d7OoC', "rol_id" => 4, "email" => "MANTO@SAMITASK.COM", "empleado_id" => 2454, "estado" => 1],
            ["name" =>"NCARDENAS", "password"=>'$2y$12$5fWPQtm.ebLblOTn4Xo1YOVQ4cSGgXP6DINP56/NW6tQ6Hj8VRzfe', "rol_id" => 4, "email" => "ADM@GMAIL.COM", "empleado_id" => 2397, "estado" => 0],
            ["name" =>"JDIAZ", "password"=>'$2y$12$F2Ut2ZQyjfthuD0IuJdbw.cRheg9aT7nWDBbWe3R8nHVYIETDodSC', "rol_id" => 4, "email" => "operaciones@gmail.com", "empleado_id" => 5678, "estado" => 1],
            ["name" =>"JORDOÑEZ", "password"=>'$2y$12$YLIAnPST4wq/B4CiphV1WeyfZASkV8j1ikatytXsyIm3BCBEtJuK.', "rol_id" => 4, "email" => "OPERACIONES1@GMAIL.COM", "empleado_id" => 1511, "estado" => 0],
            ["name" =>"GSANCHEZ", "password"=>'$2y$12$alxxZ0j2JdqxlE218RKpx.8Yu/kZ4v5Xmc./Xx8TMTKC4G4pYVMf.', "rol_id" => 4, "email" => "ADMIN@COSMIC.COM", "empleado_id" => 5694, "estado" => 0],
            ["name" =>"ana", "password"=>'$2y$12$TvkoRwlMBXTMHburIKTPpOrQLA9LvADjpbSsz.MR8sCn2WkW9Mp5a', "rol_id" => 1, "email" => "procesos@samitask.com", "empleado_id" => 4558, "estado" => 0],
            ["name" =>"Jacevedo", "password"=>'$2y$12$QQ620h5LM23K5bLd6sjzrOxs3ILyd33pBnyHmEhK5nyrhxXzJI6ku', "rol_id" => 4, "email" => "operaciones.8@lagranjavilla.com", "empleado_id" => 1636, "estado" => 1],
            ["name" =>"Jdavalos", "password"=>'$2y$12$0oayPmLuDNb6JL/7AVFVIOkonSGeUeAb8pHpMYSZ07BYm2SQMnH8i', "rol_id" => 4, "email" => "operaciones1@lagranjavilla.com", "empleado_id" => 1948, "estado" => 0],
            ["name" =>"SAETTONE", "password"=>'$2y$12$siB2uFT5P4nLOJqzuUdb0OXpnzRtwROKKcU5USm4VQf1VkRV.tx82', "rol_id" => 4, "email" => "PRUEBA@GMAI.COM", "empleado_id" => 5747, "estado" => 0],
            ["name" =>"JRAMOS", "password"=>'$2y$12$E58l3l/20jeHFBgRCDdxTe/TzztLL.hPQaye3I0ZRwck1Kfam.LgC', "rol_id" => 4, "email" => "ADMIN@GMAIL.COM", "empleado_id" => 5672, "estado" => 0],
            ["name" =>"SCHAMORRO", "password"=>'$2y$12$9Qngu.pWlo1HcVCvHn40..fFzioAj/VC/K7rB4i8GF38CapiRG5i6', "rol_id" => 4, "email" => "ADMIN5@GMAIL.COM", "empleado_id" => 397, "estado" => 1],
            ["name" =>"MALVAREZ", "password"=>'$2y$12$gj3zv3BpHNiSyPYOhHP9POVqnjJ6hhT6AqNylFdmPQfHupWjS.dM.', "rol_id" => 4, "email" => "MALVAREZ@HOTMAIL.COM", "empleado_id" => 5644, "estado" => 0],
            ["name" =>"ASALAS", "password"=>'$2y$12$iGM.jOm12gtuwiA/i/qiZeemmV53ncYLPqvXqY1Ki49G/m6Zw1b5y', "rol_id" => 4, "email" => "MOY@MOY1.COM", "empleado_id" => 4606, "estado" => 0],
            ["name" =>"DVASQUEZ", "password"=>'$2y$12$JF7h0CmggNnqfvVz.9wWSeqv0v0O9isxClmVQthPjw3vfDM3wegYS', "rol_id" => 4, "email" => "MOY2@MOY2.COM", "empleado_id" => 5780, "estado" => 0],
            ["name" =>"BAGUILAR", "password"=>'$2y$12$crMYNmoUdsSArXKkRF7BmOGpDryM/9fB3kRjeMZw5O7/frFSHdcl2', "rol_id" => 4, "email" => "OPERACIONES5@SAMITASK.COM", "empleado_id" => 497, "estado" => 1],
            ["name" =>"MJUNCHAYA", "password"=>'$2y$12$e5n8SDDnLBum6r7GeLKYmOA9c8LcwbTE8sZG1pz22ND3TDZUuAU8O', "rol_id" => 4, "email" => "CAJAS7@HOTMAIL.COM", "empleado_id" => 5793, "estado" => 1],
            ["name" =>"MINFANZON", "password"=>'$2y$12$PTaQVIkSM.PnxjWV1.DsV.4Jk5O7Vg8eKleOBS9qI51Ed//MknkVK', "rol_id" => 4, "email" => "MINFANZON@GMAIL.COM", "empleado_id" => 5870, "estado" => 1],
            ["name" =>"MANGELES", "password"=>'$2y$12$SKf/iCGt96zPyVPlhm5lQevGfkqXmRTAEhJIFs1ezUQLr2/0/Oe8i', "rol_id" => 4, "email" => "MANGELES@GMAIL.COM", "empleado_id" => 5838, "estado" => 1],
            ["name" =>"LZEÑA", "password"=>'$2y$12$DzL.TgywbzNhG6Mn4GJ3eOFk0Z6nP8vUG7btCq58liBA7E4m3tWx.', "rol_id" => 4, "email" => "BOWLING@BOWLING.COM", "empleado_id" => 5887, "estado" => 1],
            ["name" =>"GMOROCHO", "password"=>'$2y$12$33bEsim/T30iD61qmJ/odesiGV/T47CKKrAAemUtyirTlpBh1cmKu', "rol_id" => 4, "email" => "GMOROCHO@GMAIL.COM", "empleado_id" => 4609, "estado" => 0],
            ["name" =>"GPALOMINO", "password"=>'$2y$12$MCEhG47iefqfYwcYDEpktO.IsmcFCLGvIBZikrhqjnSOVJk5xpTkm', "rol_id" => 4, "email" => "GPALOMINO@GMAIL.COM", "empleado_id" => 4603, "estado" => 1],
            ["name" =>"DCUYA", "password"=>'$2y$12$CifROmxcIBcmGjTQT4q1LOW1IEnv4a0McKiIUurzSLIjPOV2SB40.', "rol_id" => 4, "email" => "LOGISTICA@SAMITASK.COM", "empleado_id" => 6848, "estado" => 1],
            ["name" =>"JGOICOCHEA", "password"=>'$2y$12$OwK3DRLTQRvy5LbHmGOw2.cTS8s97U4atq9WAkgu8lrFoIUpYooPm', "rol_id" => 4, "email" => "ADMI1N@GMAIL.COM", "empleado_id" => 6838, "estado" => 1],
            ["name" =>"SUP_AB", "password"=>'$2y$12$hJ1wUfn22hF8ty1yxgr58ujVvXngp6cnp5zA1MjEZSH0udQQuAuRu', "rol_id" => 4, "email" => "AYB@SAMITASK.COM", "empleado_id" => 6876, "estado" => 1],
            ["name" =>"ADIAZ", "password"=>'$2y$12$4tHumUOudo2OU4c4CaaMSeFMZD6kwCAEWh9Teztud7TOxqSBjQcjm', "rol_id" => 4, "email" => "ADIAZ@GMAIL.COM", "empleado_id" => 8831, "estado" => 1],
            ["name" =>"RYAURI", "password"=>'$2y$12$vS3OfxDLvNMaoVojG1hjW.PLmBwJ2wtssAqU2SnVjofNnewlVbUDq', "rol_id" => 4, "email" => "RYAURI@GMAIL.COM", "empleado_id" => 9834, "estado" => 1],
            ["name" =>"HTASAYCO", "password"=>'$2y$12$F1GsL46.FwMn/2Tset6r1ukGCp1X/VaitEubfp0rW7FuYeIs8dClS', "rol_id" => 4, "email" => "redes.1@lagranjavilla.com", "empleado_id" => 1863, "estado" => 1],
            ["name" =>"JFUENTES", "password"=>'$2y$12$J4MNO2Sym12bdGw4Y3rLoeese5/zx9jTVouztiZ8.l7nscSyC.D.O', "rol_id" => 4, "email" => "ADM@COSMICBOWLING.COM", "empleado_id" => 9848, "estado" => 1],
            ["name" =>"GROBLES", "password"=>'$2y$12$s7xfJkWDsoFknGHiYSwiXuSoaTl7yKPXiekQqa4Xe7oUICWMf.3mi', "rol_id" => 4, "email" => "GROBLES@GMAIL.COM", "empleado_id" => 1914, "estado" => 1],
            ["name" =>"MCASTRO", "password"=>'$2y$12$uZ216H1ykaQYyBdgXn7KkO.bZdXCmfm8ICZjQT.J/8EBmMjZ4mMum', "rol_id" => 4, "email" => "MACASTRO@GMAIL.COM", "empleado_id" => 9854, "estado" => 1],
            ["name" =>"MHUARCAYA", "password"=>'$2y$12$2O5/JkXY8uoczVr1huVbyegwZFX3NFH8OtfKyGOBR3DtrAy.wkP6e', "rol_id" => 4, "email" => "G@G.COM", "empleado_id" => 9899, "estado" => 1],
            ["name" =>"JSERNA", "password"=>'$2y$12$1UoS9b97mgyu3Spb1zjXROkZO4Ikc6RjhSoCNlnIZIGjS0o.sTc2y', "rol_id" => 4, "email" => "supervisor9@lagranjavilla.com", "empleado_id" => 10922, "estado" => 1],
            ["name" =>"ROJASG", "password"=>'$2y$12$Ndc0lst1ZmBmj7qgEp2o8O7iHEOcVTFVMa8oAocRREHFRVGzeY4Lm', "rol_id" => 4, "email" => "rojasg.supervisor@lagranjavilla.com", "empleado_id" => 10927, "estado" => 1],
            ["name" =>"NPADILLA", "password"=>'$2y$12$yjMRNnYzLBRZpfBCVbDF7e/orccZjwJ/N8TpQjKrYmpMbkPJsDeES', "rol_id" => 4, "email" => "nicole@granjavilla.com", "empleado_id" => 10932, "estado" => 1],
            ["name" =>"ECUBILLAS", "password"=>'$2y$12$IRs1xnvJOmSXkdxYhwiw7.711Lx.eMn1.khv3QOx10hpn/BibVkZO', "rol_id" => 4, "email" => "bowling123@samitask.com", "empleado_id" => 10942, "estado" => 1],
            ["name" =>"CGABRIEL", "password"=>'$2y$12$5H0NG9cbMe.rFOnoWDxas.sM0CFK1/nMQy6yfFcyeF.nZK/5E4W02', "rol_id" => 4, "email" => "sistemas@samitask.com", "empleado_id" => 11970, "estado" => 1],
            ["name" =>"CVICARIO", "password"=>'$2y$12$hLtQubJ7iSzk40..EPV2AOX6oy9Wgv..S7iRVxIkJNctAoPaR9qAq', "rol_id" => 4, "email" => "comercial@samitask.com", "empleado_id" => 11971, "estado" => 1],
            ["name" =>"GCAVERO", "password"=>'$2y$12$8eVb59GqLTNDOvF.WratreNrQKmju7yYG9IhbLk84wZh5is8CRQca', "rol_id" => 4, "email" => "operaciones22@lagranjavilla.com", "empleado_id" => 11963, "estado" => 1],
            ["name" =>"ALOPEZ", "password"=>'$2y$12$iMbb214taLqjmV6UllKffOCHYaX1aDpyqDA8P8aAfcQa7IMiWGAva', "rol_id" => 4, "email" => "admin@cosmicbowling.com", "empleado_id" => 11999, "estado" => 1],
            ["name" =>"DIOSALINDA", "password"=>'$2y$12$QsoDLyDPI4eoe6qEeUmhfeTWP9TCG1ZyeqNTfls1a/t59Xkog9o3.', "rol_id" => 4, "email" => "admin@syvec.com.pe", "empleado_id" => 11942, "estado" => 1],
            ["name" =>"EDWARDT", "password"=>'$2y$12$gni8zGWsdWeIjdaY7ScOW.tIW4zzLhnTFFe.aF5hxqEpdygdW2lpe', "rol_id" => 4, "email" => "infantil@lagranjavilla.com", "empleado_id" => 12050, "estado" => 1],
            ["name" =>"JPISCO", "password"=>'$2y$12$i5f.WyzyPDbAEp.8KfWa0.dHFWma7lVWiQCTcswMomn7LM6Lx5Aru', "rol_id" => 4, "email" => "granjita@lagranjavilla.com", "empleado_id" => 12079, "estado" => 1],
            ["name" =>"CCORDOVA", "password"=>'$2y$12$x.i/SEef2HjbUTblS0nKz.0ybTdMFVEpJwil8SIwk6cpFcclfEUUi', "rol_id" => 4, "email" => "ccordova@yakupark.com.pe", "empleado_id" => 11972, "estado" => 1],
            ["name" =>"GCHANG", "password"=>'$2y$12$cAapJ5v2PltK6o3LK9VkweyxT7Q98VMoL6bbJecEUik.7j.fMlVm6', "rol_id" => 4, "email" => "admin@lagranjavilla.com", "empleado_id" => 12077, "estado" => 1],
            ["name" =>"QUEVEDOM", "password"=>'$2y$12$4yICLejvB8eX3Aw4BveHee/7a0.tPQrfSyQ92.ge6lwCcNmUspZBS', "rol_id" => 4, "email" => "QUEVEDOM@GMAIL.COM", "empleado_id" => 12104, "estado" => 1],
            ["name" =>"JALARCON", "password"=>'$2y$12$IEjvLjcPHyF1P8TBKZPV/e172DTCXH/X3M7AWYbsVs9aX6xjIs7OC', "rol_id" => 4, "email" => "JALARCON@GMAIL.COM", "empleado_id" => 12107, "estado" => 1],
            ["name" =>"HPISCOYA", "password"=>'$2y$12$A2IkJpxeROhd2JVPQ6ftY.JQ4nL9RMlaIdCC1TUXbSmOexUc3IrE2', "rol_id" => 4, "email" => "HPISCOYA@GMAIL.COM", "empleado_id" => 9905, "estado" => 1],
            ["name" =>"DARINKA", "password"=>'$2y$12$SN2f5T6Em.y6qjOaB..pnOcI5J.QGbbA8AKBEO7F9Lu/DDzNgISUC', "rol_id" => 4, "email" => "REDES@GMAIL.COM", "empleado_id" => 12188, "estado" => 1],
            ["name" =>"fajardo", "password"=>'$2y$12$oyURHtgMvyjXVGF84ZzHXOGCgukJYOQOmKIRCvjZqkv31S3UX0KoG', "rol_id" => 4, "email" => "fajardo@gmail.com", "empleado_id" => 12137, "estado" => 1],
            ["name" =>"FSARRIA", "password"=>'$2y$12$fULbfp3Erc3GSeWi3jrQ/uHOuE6gYiTslqZh94Af640orlRNdPbZ6', "rol_id" => 4, "email" => "FSARRIA@GMAIL.COM", "empleado_id" => 12232, "estado" => 1],
            ["name" =>"EAYQUIPA", "password"=>'$2y$12$zcfkOwA2k0foMTtAO5kuIu4o1ji95pX8t7qfzg.rAiDlbNCZKIE.u', "rol_id" => 4, "email" => "EAYQUIPA@LAGRANJAVILLA.COM", "empleado_id" => 12282, "estado" => 1],
            ["name" =>"KHURTADO", "password"=>'$2y$12$B890ZMKgCKc1Rx63x34jkORlP1NRgOUcTVKDb2iGb8rfVkqTPiywi', "rol_id" => 4, "email" => "KHURTADO@DREAMSCOMPANY.COM", "empleado_id" => 12263, "estado" => 1],
            ["name" =>"OCAMPOS", "password"=>'$2y$12$XccILO/R361tw17Ta2iheOk/eQr1rIEG6C0yUSHQKRM0oGT5CwaF6', "rol_id" => 4, "email" => "OCAMPOS@LAGRANJAVILLA.COM", "empleado_id" => 1853, "estado" => 1],
            ["name" =>"AMONTES", "password"=>'$2y$12$pfAh0aKXlhXF4bYgfBFvkeOavzpXCP7TGbhwXmTIZME0dnehJ0FPO', "rol_id" => 4, "email" => "AMONTES@GV.COM", "empleado_id" => 12332, "estado" => 1],
            ["name" =>"JCERCADO", "password"=>'$2y$12$Zgo95ES3iB2ChHUEnS5VcuyhBPApPdcfy0RodxJLtiPFqZD94gsXW', "rol_id" => 4, "email" => "JCERCADO@GV.COM", "empleado_id" => 12337, "estado" => 1],
            ["name" =>"ADMIN", "password"=>'$2y$12$F2Ut2ZQyjfthuD0IuJdbw.cRheg9aT7nWDBbWe3R8nHVYIETDodSC', "rol_id" => 1, "email" => "sistemas.ti@samitask.com", "empleado_id" => 115, "estado" => 1],
            ["name" =>"RRHH1", "password"=>'$2y$12$e42rr2ih/6d.y3NZYuXs3O5HJaeR1cs34P7lBxGZ.YBEW4NduWxqO', "rol_id" => 2, "email" => "rrhh.1@samitask.com", "empleado_id" => 106, "estado" => 1],
            ["name" =>"MEDICO", "password"=>'$2y$12$u4N7jJgjU0BI/HZdGOo/4O33EAcByUa5KDEbQHWZYeBuxtajvhsfS', "rol_id" => 3, "email" => "medocupacional@lagranjavilla.com", "empleado_id" => 101, "estado" => 1],
            ["name" =>"RRHH2", "password"=>'$2y$12$lAxQqqewOWmA6CPuMjH41.zliI2B18dqCS774pgyWQmikXSLkgpdK', "rol_id" => 2, "email" => "rrhh.2@samitask.com", "empleado_id" => 3521, "estado" => 1],
        ]);
    }
}
